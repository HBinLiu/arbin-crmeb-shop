package wxcallback

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"net/http/httputil"
	"net/url"
	"strings"

	"github.com/WeixinCloud/wxcloudrun-wxcomponent/comm/custom"
	"github.com/WeixinCloud/wxcloudrun-wxcomponent/comm/errno"
	"github.com/WeixinCloud/wxcloudrun-wxcomponent/comm/log"
	wxbase "github.com/WeixinCloud/wxcloudrun-wxcomponent/comm/wx/base"
	"github.com/WeixinCloud/wxcloudrun-wxcomponent/db/dao"
	"github.com/WeixinCloud/wxcloudrun-wxcomponent/db/model"
	"github.com/gin-gonic/gin"
	"github.com/gin-gonic/gin/binding"
)

func newReverseProxy(target *url.URL) *httputil.ReverseProxy {
	targetQuery := target.RawQuery
	director := func(req *http.Request) {
		req.URL.Scheme = target.Scheme
		req.URL.Host = target.Host
		req.Host = target.Host
		req.URL.Path = target.Path
		if targetQuery == "" || req.URL.RawQuery == "" {
			req.URL.RawQuery = targetQuery + req.URL.RawQuery
		} else {
			req.URL.RawQuery = targetQuery + "&" + req.URL.RawQuery
		}
		if _, ok := req.Header["User-Agent"]; !ok {
			req.Header.Set("User-Agent", "")
		}
	}
	errorHandler := func(rw http.ResponseWriter, req *http.Request, err error) {
		log.Errorf("http: proxy error: %v", err)
		result, _ := json.Marshal(errno.ErrSystemError.WithData(err.Error()))
		rw.Header().Set("Content-Type", "application/json")
		rw.Write([]byte(result))
	}
	return &httputil.ReverseProxy{Director: director, ErrorHandler: errorHandler}
}

func proxyCallbackMsg(infoType string, msgType string, event string, body string, c *gin.Context) (bool, error) {
	rule, err := dao.GetWxCallBackRuleWithCache(infoType, msgType, event)
	if err != nil {
		log.Error(err)
		return false, err
	}
	if rule != nil && rule.Open != 0 && rule.Type == model.PROXYTYPE_HTTP {
		var proxyConfig model.HttpProxyConfig
		if err = json.Unmarshal([]byte(rule.Info), &proxyConfig); err != nil {
			log.Errorf("Unmarshal err, %v", err)
			return false, err
		}
		path := strings.Replace(proxyConfig.Path, "$APPID$", c.Param("appid"), -1)
		log.Infof("proxy: %v, real path %s", rule, path)
		var target *url.URL
		if target, err = url.Parse(fmt.Sprintf("http://127.0.0.1:%d%s", proxyConfig.Port, path)); err != nil {
			log.Errorf("url Parse error: %v", err)
			return false, err
		}
		proxy := newReverseProxy(target)
		c.Request.Body = ioutil.NopCloser(bytes.NewBuffer([]byte(body)))
		proxy.ServeHTTP(c.Writer, c.Request)
		return true, nil
	}
	return false, nil
}

// validateMessage 验证消息签名并解密消息体
// 返回值：是否验证通过，原始请求体，解密后的消息体
func validateMessage(c *gin.Context) (bool, []byte, []byte) {
	// 1. 获取URL参数
	msgSignature := c.Query("msg_signature")
	timestamp := c.Query("timestamp")
	nonce := c.Query("nonce")
	// 2. 读取XML消息体
	body, _ := ioutil.ReadAll(c.Request.Body)
	var encryptedBody struct {
		Encrypt string `xml:"Encrypt"`
	}
	if err := binding.XML.BindBody(body, &encryptedBody); err != nil {
		c.JSON(http.StatusOK, errno.ErrInvalidParam.WithData(err.Error()))
		return false, nil, nil
	}
	// 3. 初始化消息解密器
	token := wxbase.GetToken()
	aesKey := wxbase.GetAesKey()
	appId := wxbase.GetAppid()
	messageCrypter, _ := custom.NewMessageCrypter(token, aesKey, appId)
	// 4. 验证消息签名
	signature := messageCrypter.GetSignature(timestamp, nonce, encryptedBody.Encrypt)
	if signature != msgSignature {
		log.Errorf("Signature mismatch: expected %s, got %s", signature, msgSignature)
		c.JSON(http.StatusOK, errno.ErrInvalidParam.WithData("signature mismatch"))
		return false, nil, nil
	}
	// 5. 解析解密后的消息体
	decrypted, appIdDecrypted, eil := messageCrypter.Decrypt(encryptedBody.Encrypt)
	if eil != nil {
		log.Errorf("Decrypt error: %v", eil)
		c.JSON(http.StatusOK, errno.ErrSystemError.WithData(eil.Error()))
		return false, nil, nil
	}
	if appIdDecrypted != appId {
		log.Errorf("appId mismatch: expected %s, got %s", appId, appIdDecrypted)
		c.JSON(http.StatusOK, errno.ErrInvalidParam.WithData("appId mismatch"))
		return false, nil, nil
	}
	return true, body, decrypted
}

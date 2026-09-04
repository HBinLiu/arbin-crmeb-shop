package wxcallback

import (
	"net/http"
	"time"

	"github.com/WeixinCloud/wxcloudrun-wxcomponent/comm/errno"
	"github.com/WeixinCloud/wxcloudrun-wxcomponent/comm/log"
	"github.com/WeixinCloud/wxcloudrun-wxcomponent/comm/wx"

	wxbase "github.com/WeixinCloud/wxcloudrun-wxcomponent/comm/wx/base"
	"github.com/WeixinCloud/wxcloudrun-wxcomponent/db/dao"
	"github.com/WeixinCloud/wxcloudrun-wxcomponent/db/model"
	"github.com/gin-gonic/gin"
	"github.com/gin-gonic/gin/binding"
)

type wxCallbackComponentRecord struct {
	CreateTime int64  `xml:"CreateTime"`
	InfoType   string `xml:"InfoType"`
}

func componentHandler(c *gin.Context) {
	// 验证消息签名并解密消息体
	valid, body, decrypted := validateMessage(c)
	if !valid {
		return
	}
	// 保存消息到数据库
	var json wxCallbackComponentRecord
	if err := binding.XML.BindBody(decrypted, &json); err != nil {
		c.JSON(http.StatusOK, errno.ErrInvalidParam.WithData(err.Error()))
		return
	}
	r := model.WxCallbackComponentRecord{
		CreateTime:  time.Unix(json.CreateTime, 0),
		ReceiveTime: time.Now(),
		InfoType:    json.InfoType,
		PostBody:    string(decrypted),
	}
	if json.CreateTime == 0 {
		r.CreateTime = time.Unix(1, 0)
	}
	if err := dao.AddComponentCallBackRecord(&r); err != nil {
		c.JSON(http.StatusOK, errno.ErrSystemError.WithData(err.Error()))
		return
	}

	// 处理授权相关的消息
	var err error
	switch json.InfoType {
	case "component_verify_ticket":
		err = ticketHandler(&decrypted)
	case "authorized":
		fallthrough
	case "updateauthorized":
		err = newAuthHander(&decrypted)
	case "unauthorized":
		err = unAuthHander(&decrypted)
	}
	if err != nil {
		log.Error(err)
		c.JSON(http.StatusOK, errno.ErrSystemError.WithData(err.Error()))
		return
	}

	// 转发到用户配置的地址
	var proxyOpen bool
	proxyOpen, err = proxyCallbackMsg(json.InfoType, "", "", string(body), c)
	if err != nil {
		log.Error(err)
		c.JSON(http.StatusOK, errno.ErrSystemError.WithData(err.Error()))
		return
	}
	if !proxyOpen {
		c.String(http.StatusOK, "success")
	}
}

type ticketRecord struct {
	ComponentVerifyTicket string `xml:"ComponentVerifyTicket"`
}

func ticketHandler(body *[]byte) error {
	var record ticketRecord
	if err := binding.XML.BindBody(*body, &record); err != nil {
		return err
	}
	log.Info("[new ticket]" + record.ComponentVerifyTicket)
	if err := wxbase.SetTicket(record.ComponentVerifyTicket); err != nil {
		return err
	}
	return nil
}

type newAuthRecord struct {
	CreateTime                   int64  `xml:"CreateTime"`
	AuthorizerAppid              string `xml:"AuthorizerAppid"`
	AuthorizationCode            string `xml:"AuthorizationCode"`
	AuthorizationCodeExpiredTime int64  `xml:"AuthorizationCodeExpiredTime"`
}

func newAuthHander(body *[]byte) error {
	var record newAuthRecord
	var err error
	var refreshtoken string
	var appinfo wx.AuthorizerInfoResp
	if err = binding.XML.BindBody(*body, &record); err != nil {
		return err
	}
	if refreshtoken, err = queryAuth(record.AuthorizationCode); err != nil {
		return err
	}
	if err = wx.GetAuthorizerInfo(record.AuthorizerAppid, &appinfo); err != nil {
		return err
	}
	if err = dao.CreateOrUpdateAuthorizerRecord(&model.Authorizer{
		Appid:         record.AuthorizerAppid,
		AppType:       appinfo.AuthorizerInfo.AppType,
		ServiceType:   appinfo.AuthorizerInfo.ServiceType.Id,
		NickName:      appinfo.AuthorizerInfo.NickName,
		UserName:      appinfo.AuthorizerInfo.UserName,
		HeadImg:       appinfo.AuthorizerInfo.HeadImg,
		QrcodeUrl:     appinfo.AuthorizerInfo.QrcodeUrl,
		PrincipalName: appinfo.AuthorizerInfo.PrincipalName,
		RefreshToken:  refreshtoken,
		FuncInfo:      appinfo.AuthorizationInfo.StrFuncInfo,
		VerifyInfo:    appinfo.AuthorizerInfo.VerifyInfo.Id,
		AuthTime:      time.Unix(record.CreateTime, 0),
	}); err != nil {
		return err
	}
	return nil
}

type queryAuthReq struct {
	ComponentAppid    string `wx:"component_appid"`
	AuthorizationCode string `wx:"authorization_code"`
}

type authorizationInfo struct {
	AuthorizerRefreshToken string `wx:"authorizer_refresh_token"`
}
type queryAuthResp struct {
	AuthorizationInfo authorizationInfo `wx:"authorization_info"`
}

func queryAuth(authCode string) (string, error) {
	req := queryAuthReq{
		ComponentAppid:    wxbase.GetAppid(),
		AuthorizationCode: authCode,
	}
	var resp queryAuthResp
	_, body, err := wx.PostWxJsonWithComponentToken("/cgi-bin/component/api_query_auth", "", req)
	if err != nil {
		return "", err
	}
	if err := wx.WxJson.Unmarshal(body, &resp); err != nil {
		log.Errorf("Unmarshal err, %v", err)
		return "", err
	}
	return resp.AuthorizationInfo.AuthorizerRefreshToken, nil
}

type unAuthRecord struct {
	CreateTime      int64  `xml:"CreateTime"`
	AuthorizerAppid string `xml:"AuthorizerAppid"`
}

func unAuthHander(body *[]byte) error {
	var record unAuthRecord
	var err error
	if err = binding.XML.BindBody(*body, &record); err != nil {
		log.Errorf("bind err %v", err)
		return err
	}
	if err := dao.DelAuthorizerRecord(record.AuthorizerAppid); err != nil {
		log.Errorf("DelAuthorizerRecord err %v", err)
		return err
	}
	return nil
}

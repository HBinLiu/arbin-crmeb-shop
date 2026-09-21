<template>
  <div>
    <el-card :bordered="false" shadow="never" :body-style="{ padding: 0 }">
      <div class="padding-add">
        <el-form :model="formValidate" inline @submit.native.prevent>
          <el-form-item label="搜索：">
            <el-input clearable placeholder="名称或分销码" v-model="formValidate.keyword" class="form_content_width" />
          </el-form-item>
          <el-form-item>
            <el-button type="primary" v-db-click @click="userSearchs">查询</el-button>
          </el-form-item>
        </el-form>
      </div>
    </el-card>
    <el-card :bordered="false" shadow="never" class="ivu-mt mt16" :body-style="{ padding: '0 20px 20px' }">
      <el-button class="mt20" type="primary" v-db-click @click="openAdd">添加分销码</el-button>
      <el-table class="mt14" :data="tableList" v-loading="loading">
        <el-table-column label="名称" min-width="140" prop="title" />
        <el-table-column label="分销码" min-width="120" prop="code" />
        <el-table-column label="已用/上限" min-width="110">
          <template slot-scope="scope">{{ scope.row.used_num }}/{{ scope.row.limit_num }}</template>
        </el-table-column>
        <el-table-column label="过期时间" min-width="160" prop="expire_time" />
        <el-table-column label="状态" min-width="90" prop="state" />
        <el-table-column label="添加时间" min-width="160" prop="add_time" />
        <el-table-column label="操作" fixed="right" width="220">
          <template slot-scope="scope">
            <a v-db-click @click="openQr(scope.row)">二维码</a>
            <el-divider direction="vertical" />
            <a v-db-click @click="openRecord(scope.row)">扫码记录</a>
            <el-divider direction="vertical" />
            <a v-db-click @click="changeStatus(scope.row)">{{ scope.row.status == 1 ? '停用' : '启用' }}</a>
            <el-divider direction="vertical" />
            <a v-db-click @click="del(scope.row)">删除</a>
          </template>
        </el-table-column>
      </el-table>
      <div class="acea-row row-right page">
        <pagination v-if="total" :total="total" :page.sync="formValidate.page" :limit.sync="formValidate.limit" @pagination="getList" />
      </div>
    </el-card>

    <el-dialog :visible.sync="addVisible" title="添加分销码" width="480px" :close-on-click-modal="false">
      <el-form ref="addForm" :model="addForm" :rules="rules" label-width="100px">
        <el-form-item label="名称：" prop="title">
          <el-input v-model="addForm.title" maxlength="30" placeholder="例如：门店活动" />
        </el-form-item>
        <el-form-item label="扫码数量：" prop="limit_num">
          <el-input-number v-model="addForm.limit_num" :min="1" :max="100000" />
        </el-form-item>
        <el-form-item label="过期时间：" prop="expire_time">
          <el-date-picker
            v-model="addForm.expire_time"
            type="datetime"
            value-format="yyyy-MM-dd HH:mm:ss"
            placeholder="不填则永不过期"
            clearable
          />
        </el-form-item>
      </el-form>
      <div slot="footer">
        <el-button v-db-click @click="addVisible = false">取消</el-button>
        <el-button type="primary" v-db-click @click="submitAdd">确定</el-button>
      </div>
    </el-dialog>

    <el-dialog :visible.sync="qrVisible" title="分销码" width="640px">
      <div v-loading="qrLoading" class="qr-wrap">
        <div class="qr-item">
          <div class="qr-title">二维码</div>
          <img v-if="qr.h5_code" :src="qr.h5_code" />
          <div class="qr-tip">微信或浏览器扫码，打开领取页</div>
        </div>
        <div class="qr-item">
          <div class="qr-title">小程序码</div>
          <img v-if="qr.routine_code" :src="qr.routine_code" />
          <div v-else class="qr-tip">{{ qr.routine_error || '生成中' }}</div>
          <div class="qr-tip">需已发布包含分销码页面的小程序</div>
        </div>
      </div>
    </el-dialog>

    <el-dialog :visible.sync="recordVisible" :title="recordTitle" width="720px">
      <el-table :data="recordList" v-loading="recordLoading">
        <el-table-column label="UID" width="90" prop="uid" />
        <el-table-column label="昵称" min-width="140" prop="nickname" />
        <el-table-column label="手机号" min-width="140" prop="phone" />
        <el-table-column label="开通时间" min-width="160" prop="add_time" />
      </el-table>
      <div class="acea-row row-right page">
        <pagination
          v-if="recordTotal"
          :total="recordTotal"
          :page.sync="recordPage.page"
          :limit.sync="recordPage.limit"
          @pagination="getRecord"
        />
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { spreadCodeList, spreadCodeSave, spreadCodeRecord, spreadCodeQrcode, spreadCodeStatus } from '@/api/agent';

export default {
  name: 'agent_spread_code',
  data() {
    return {
      loading: false,
      tableList: [],
      total: 0,
      formValidate: {
        keyword: '',
        page: 1,
        limit: 15,
      },
      addVisible: false,
      addForm: {
        title: '',
        limit_num: 1,
        expire_time: '',
      },
      rules: {
        title: [{ required: true, message: '请输入名称', trigger: 'blur' }],
        limit_num: [{ required: true, message: '请输入数量', trigger: 'change' }],
      },
      qrVisible: false,
      qrLoading: false,
      qr: {},
      recordVisible: false,
      recordLoading: false,
      recordTitle: '扫码记录',
      recordId: 0,
      recordList: [],
      recordTotal: 0,
      recordPage: {
        page: 1,
        limit: 10,
      },
    };
  },
  created() {
    this.getList();
  },
  methods: {
    userSearchs() {
      this.formValidate.page = 1;
      this.getList();
    },
    getList() {
      this.loading = true;
      spreadCodeList(this.formValidate)
        .then((res) => {
          this.tableList = res.data.list;
          this.total = res.data.count;
          this.loading = false;
        })
        .catch((res) => {
          this.loading = false;
          this.$message.error(res.msg);
        });
    },
    openAdd() {
      this.addForm = { title: '', limit_num: 1, expire_time: '' };
      this.addVisible = true;
      this.$nextTick(() => this.$refs.addForm && this.$refs.addForm.clearValidate());
    },
    submitAdd() {
      this.$refs.addForm.validate((valid) => {
        if (!valid) return;
        spreadCodeSave(this.addForm)
          .then((res) => {
            this.$message.success(res.msg);
            this.addVisible = false;
            this.getList();
          })
          .catch((res) => {
            this.$message.error(res.msg);
          });
      });
    },
    openQr(row) {
      this.qr = {};
      this.qrVisible = true;
      this.qrLoading = true;
      spreadCodeQrcode(row.id)
        .then((res) => {
          this.qr = res.data;
          this.qrLoading = false;
        })
        .catch((res) => {
          this.qrLoading = false;
          this.$message.error(res.msg);
        });
    },
    openRecord(row) {
      this.recordId = row.id;
      this.recordTitle = row.title + ' 扫码记录';
      this.recordPage.page = 1;
      this.recordVisible = true;
      this.getRecord();
    },
    getRecord() {
      this.recordLoading = true;
      spreadCodeRecord(this.recordId, this.recordPage)
        .then((res) => {
          this.recordList = res.data.list;
          this.recordTotal = res.data.count;
          this.recordLoading = false;
        })
        .catch((res) => {
          this.recordLoading = false;
          this.$message.error(res.msg);
        });
    },
    changeStatus(row) {
      const status = row.status == 1 ? 0 : 1;
      spreadCodeStatus(row.id, status)
        .then((res) => {
          this.$message.success(res.msg);
          this.getList();
        })
        .catch((res) => {
          this.$message.error(res.msg);
        });
    },
    del(row) {
      this.$modalSure({
        title: '删除分销码',
        url: `agent/spread_code/del/${row.id}`,
        method: 'DELETE',
      }).then((res) => {
        this.$message.success(res.msg);
        this.getList();
      });
    },
  },
};
</script>

<style scoped>
.qr-wrap {
  display: flex;
  justify-content: space-around;
  min-height: 220px;
}
.qr-item {
  width: 240px;
  text-align: center;
}
.qr-item img {
  width: 180px;
  height: 180px;
}
.qr-title {
  margin-bottom: 12px;
  font-weight: 600;
}
.qr-tip {
  margin-top: 8px;
  color: #999;
  font-size: 12px;
}
</style>

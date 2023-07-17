<template>
  <Modal v-model="modals" scrollable title="取消寄件" class="order_box" :closable="false">
    <Form ref="formValidate" :model="formValidate" :rules="ruleValidate" :label-width="80" @submit.native.prevent>
      <FormItem label="备注：" prop="msg">
        <Input
          v-model="formValidate.msg"
          :maxlength="200"
          show-word-limit
          type="textarea"
          placeholder="取消寄件备注"
          style="width: 100%"
        />
      </FormItem>
    </Form>
    <div slot="footer">
      <Button type="primary" @click="putRemark('formValidate')">提交</Button>
      <Button @click="cancel('formValidate')">取消</Button>
    </div>
  </Modal>
</template>

<script>
import { shipmentCancelOrder } from '@/api/order';
export default {
  name: 'orderMark',
  data() {
    return {
      formValidate: {
        msg: '',
      },
      modals: false,
      ruleValidate: {
        msg: [
          { required: true, message: '请输入备注信息', trigger: 'blur' },
          // { type: 'string', min: 20, message: 'Introduce no less than 20 words', trigger: 'blur' }
        ],
      },
    };
  },
  props: {
    orderId: Number,
  },
  methods: {
    cancel(name) {
      this.modals = false;
      this.$refs[name].resetFields();
    },
    putRemark(name) {
      let data = {
        msg: this.formValidate.msg,
      };
      this.$refs[name].validate((valid) => {
        if (valid) {
          shipmentCancelOrder(this.orderId, data)
            .then(async (res) => {
              this.$Message.success(res.msg);
              this.modals = false;
              this.$refs[name].resetFields();
              this.$emit('submitFail');
            })
            .catch((res) => {
              this.$Message.error(res.msg);
            });
        } else {
          this.$Message.warning('请填写备注信息');
        }
      });
    },
  },
};
</script>

<style scoped></style>

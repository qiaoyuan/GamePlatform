<template>
  <div class="w-login-wrap">
    <div class="w-login-box">
      <div class="f26 b white tc mb25">用户登录</div>
      <div>
        <el-form :model="loginForm" ref="loginForm" label-position="left" class="w-login-form" :status-icon="true" :inline-message="true" :show-message="false">
          <el-form-item prop="username" :required="true">
            <el-input
              v-model.trim="loginForm.username"
              auto-complete="off"
              placeholder="请输入帐号"
            />
          </el-form-item>
          <el-form-item prop="password" :required="true">
            <el-input
              v-model.trim="loginForm.password"
              auto-complete="off"
              placeholder="请输入密码"
              show-password
              @keyup.enter.native="handleLogin"
            />
          </el-form-item>
          <el-form-item>
            <el-button
              :loading="loading"
              class="w-login-btn w100"
              type="primary"
              :disabled="loading"
              @click.native.prevent="handleLogin"
            >
              <span v-if="!loading">登录</span>
              <span v-else>获取中...</span>
            </el-button>
          </el-form-item>
        </el-form>
      </div>
    </div>
    <div class="w-bg-icon">
      <img :src="adImg">
      <div><span>关键数据，了然于胸</span><span>经营趋势，尽在掌控</span></div>
    </div>
    <div id="holder"></div>
  </div>
</template>
<script>
import Waves from '@/utils/login_animation'
export default {
  data() {
    return {
      adImg: require('@/assets/image/icon-1.png'),
      loginForm: {
       username:'',
       password:'',
       checked: false,
      },
      loading: false,
    }
  },
  mounted() {
    if (!this.$w_fun.isMobile()) {
      this.$nextTick(() => {
        const waves = new Waves('#holder', {
          waves: 3,
          resize: true,
          amplitude: .7,
          width: 500,
          hue: [13, 13],
        });
        waves.animate();
      })
    }
  },
  methods: {
    handleLogin() {
      this.$store.dispatch('login', this.loginForm).then(r => {
        if (this.$route.query.redirect) {
          this.$router.push(this.$route.query.redirect)
        } else {
          this.$router.push('/index')
        }
      })
    }
  },
}
</script>
<style lang="scss" scoped>
.w-login-wrap{
  display: flex;
  flex-direction: column;
  height: 100%;
  background-image: linear-gradient(0, #323259, #323259);
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  .w-bg-icon{
    position: absolute;
    z-index: 1;
    width: 630px;
    height: 630px;
    left: 50%;
    top: 50%;
    margin-top: -320px;
    margin-left: -670px;
    img{
      position: relative;
      z-index: 0;
    }
    >div{
      text-align: center;
      margin-top: -100px;
      position: relative;
      z-index: 1;
      *{
        font-size: 26px;
        color: #afd8ff;
        font-weight: bold;
        padding: 0 20px;
        text-shadow: 0px 3px rgba(0,0,0,.1);
      }
    }
  }
  #holder{
    position: absolute;
    z-index: 0;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
  }
  .w-login-box{
    position: absolute;
    right: 180px;
    top: 50%;
    margin-top: -160px;
    z-index: 10;
    width: 300px;
    & :deep .el-input__inner{
      height: 50px;
      line-height: 50px;
      border-radius: 8px;
      box-shadow: 0 0 5px 5px rgba(0,0,0,.04);
    }
    & :deep .el-button--primary{
      height: 50px;
      box-sizing: border-box;
      border-radius: 8px;
      background-color: #0047e0;
      border-color: #0047e0;
      box-shadow: 0 0 5px 5px rgba(0,0,0,.04);
      transition: all .1s ease .1s;
      &:hover{
        background-color: #05afff;
        border-color: #05afff;
      }
      *{
        font-size: 18px;
      }
    }
    & :deep .el-checkbox__label{
      color: #ffffff;
    }
  }
}
</style>
<style lang="scss" scoped>
@media only screen and (max-width: 600px) {
  .w-login-wrap{
    .w-bg-icon{
      display: none;
    }
    .w-login-box{
      left: 50%;
      right: auto;
      margin-left: -150px;
    }
  }
}
</style>
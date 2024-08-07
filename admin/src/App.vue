<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script>
export default {
  name:'app',
}
</script>
<style lang="scss">
.el-drawer__header>:first-child:focus {
		outline: 0;
	}
	.tox-tinymce-aux {
    z-index: 5000 !important;
  }
	.clear-float {
	  overflow: hidden;
	}
	.del-audio {
	  margin-top: 12px;
	  margin-left: 8px;
	  display: inline-block;
	  width: 30px;
	  text-align: center;
	  height: 30px;
	  line-height: 30px;
	  color: #ca5d5d;
	  border-radius: 5px;
	  background: #d6e0e6;
	  cursor: pointer;
	}
	.show-dots {
	  white-space: nowrap;
	  overflow: hidden;
	  text-overflow: ellipsis;
	}
	.el-dialog{
	    display: flex;
	    display: -ms-flex; /* 兼容IE */
	    flex-direction: column;
	    -ms-flex-direction: column; /* 兼容IE */
	    margin:0 !important;
	    position:absolute;
	    top:50%;
	    left:50%;
	    transform:translate(-50%,-50%);
	    max-height:calc(100% - 30px);
	    max-width:calc(100% - 30px);
	  }
	.el-dialog .el-dialog__body{
	    max-height: 100%;
	    flex: 1;
	    -ms-flex: 1 1 auto; /* 兼容IE */
	    overflow-y: auto;
	    overflow-x: hidden;
	}
	.el-dialog__wrapper {
	    /*隐藏ie和edge中遮罩的滚动条*/
	    overflow: hidden;
	}
  .el-dialog:not(.is-fullscreen) {
      margin-top: 0 !important;
  }
  .el-dialog__header{
    background-color: #f1f1f1;
    padding: 15px;
    font-weight: bold;
  }
</style>

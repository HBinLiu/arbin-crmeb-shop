const path = require('path');
const Setting = require('./src/setting.env');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
const MonacoWebpackPlugin = require('monaco-editor-webpack-plugin');

// 路径解析辅助函数
const resolve = (dir) => path.join(__dirname, dir);

// 环境变量
const isProd = process.env.NODE_ENV === 'production';

module.exports = {
  // 基础配置
  outputDir: Setting.outputDir,
  runtimeCompiler: true,
  productionSourceMap: false, // 关闭生产环境下的SourceMap映射文件
  publicPath: '/admin',
  assetsDir: 'system_static',
  indexPath: 'index.html',
  lintOnSave: false,
  // 开发服务器配置
  devServer: {
    port: 1617,
  },
  // webpack配置
  configureWebpack: (config) => {
    // 生产环境特定配置
    if (isProd) {
      // JS文件压缩配置
      config.plugins.push(
        new UglifyJsPlugin({
          uglifyOptions: {
            compress: {
              drop_debugger: true,
              drop_console: true, // 生产环境自动删除console
              pure_funcs: ['console.log'], // 移除console.log
            },
          },
          sourceMap: false,
          parallel: true, // 使用多进程并行运行来提高构建速度
        }),
      );
      // 代码分割配置（已注释，如需启用可取消注释）
      /*
      config.optimization = {
        runtimeChunk: 'single',
        splitChunks: {
          chunks: 'all',
          maxInitialRequests: Infinity,
          minSize: 20000,
          cacheGroups: {
            vendor: {
              test: /[\\/]node_modules[\\/]/,
              name(module) {
                const packageName = module.context.match(/[\\/]node_modules[\\/](.*?)([\\/]|$)/)[1];
                return `npm.${packageName.replace('@', '')}`;
              },
            },
          },
        },
      };
      */
    }
  },

  // webpack链式配置
  chainWebpack: (config) => {
    // 删除预加载
    config.plugins.delete('prefetch');

    // 设置路径别名
    config.resolve.alias.set('@', resolve('src')).set('_c', resolve('src/components')).set('@api', resolve('src/api'));

    // Vue文件规则配置
    config.module
      .rule('vue')
      .test(/\.vue$/)
      .end();

    // Node配置
    config.node.set('__dirname', true).set('__filename', true);

    // Monaco编辑器插件
    config.plugin('monaco').use(new MonacoWebpackPlugin());
  },
};

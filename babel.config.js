module.exports = {
  presets: [
    [
      '@babel/preset-env',
      {
        targets: {
          node: 'current',
        },
      },
    ],
    '@babel/preset-react',
    '@babel/preset-typescript',
  ],
  plugins: [
    function () {
      return {
        visitor: {
          MetaProperty(path) {
            /**
              replace "import.meta" by "process" to access env in jest test
              env for vite : https://vitejs.dev/guide/env-and-mode.html#env-variables
              issue for jest : https://github.com/vitejs/vite/issues/1149
             **/
            path.replaceWithSourceString('process')
          },
        },
      }
    },
  ],
}

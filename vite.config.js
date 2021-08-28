import { defineConfig } from 'vite'
import reactRefresh from '@vitejs/plugin-react-refresh'
import {resolve} from 'path'

const twigRefreshPlugin = {
  name: 'twig-refresh',
  configureServer({watcher, ws}) {
    watcher.add(resolve('templates/*.html'));
    watcher.on('change', function(path) {
      if (path.endsWith('.html')) {
        ws.send({
          type: 'full-reload'
        })
      }
    })
  }
}


export default defineConfig({
  plugins: [reactRefresh(), twigRefreshPlugin],
  root: './assets',
  base: '/assets',
  server: {
    watch: {
      disableGlobbing: false, // required for the twig plugin
    }
  },
  build: {
    manifest: true,
    assetsDir: '',
    outDir: '../web/assets/',
    rollupOptions: {
      output: {
        // manualChunks: undefined
      },
      input: {
        'main.jsx': './assets/main.jsx'
      }
    }
  }
})

// // https://vitejs.dev/config/
// export default defineConfig ({
//   plugins: (reactRefresh(), twigRefreshPlugin),
//   root: './assets',
//   base: '/ assets /',
//   server: {
//     watch: {
//       disableGlobbing: false, // required for the twig plugin
//     }
//   },
//   build: {
//     manifest: true,
//     assetsDir: '',
//     outDir: './web/assets/',
//     rollupOptions: {
//       output: {
//         manualChunks: undefined // We don't want to create a vendors file, because we only have an entry point here
//       },
//       input: {
//         'main.jsx': './assets/main.jsx'
//       }
//     }
//   }
// })

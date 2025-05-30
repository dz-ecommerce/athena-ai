const mix = require('laravel-mix');
const path = require('path');
const webpack = require('webpack');

// Configure Mix
mix
  .setPublicPath('assets/dist')
  .browserSync({
    proxy: 'localhost', // Your local development URL
    files: [
      'assets/src/**/*.js',
      'assets/src/**/*.scss',
      'templates/**/*.php',
      'includes/**/*.php',
    ],
    open: false,
    injectChanges: true,
  })
  .webpackConfig({
    stats: 'errors-only',
    devtool: mix.inProduction() ? false : 'source-map',
    externals: {
      jquery: 'jQuery',
    },
    module: {
      rules: [
        {
          test: /\.(js|vue)$/,
          enforce: 'pre',
          exclude: /node_modules/,
          loader: 'eslint-loader',
          options: {
            fix: true,
          },
        },
      ],
    },
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'assets/src'),
      },
    },
  })
  .options({
    processCssUrls: false,
    postCss: [
      require('postcss-import')(),
      require('tailwindcss/nesting')(require('postcss-nesting')),
      require('tailwindcss')({ config: './tailwind.config.js' }),
      require('autoprefixer')(),
    ],
  });

// Compile JavaScript
mix.js('assets/src/js/app.js', 'js')
  .vue()
  .sourceMaps()
  .version();

// Compile SCSS
mix.sass('assets/src/scss/app.scss', 'css')
  .sourceMaps()
  .version();

// Copy static assets
mix.copy('assets/src/fonts', 'assets/dist/fonts');

// Disable success notifications in development
if (!mix.inProduction()) {
  mix.disableSuccessNotifications();
}

// Only run in production
if (mix.inProduction()) {
  mix.version();
}

// Add custom webpack configuration
mix.webpackConfig({
  plugins: [
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
      'window.jQuery': 'jquery',
    }),
  ],
});

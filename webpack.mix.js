const mix = require('laravel-mix');

// Vereinfachte Konfiguration - nur für Tailwind CSS
mix
  .setPublicPath('assets/dist')
  .options({
    processCssUrls: false,
    postCss: [
      require('postcss-import')(),
      require('tailwindcss')({ config: './tailwind.config.js' }),
      require('autoprefixer')(),
    ],
  });

// Nur CSS kompilieren
mix.postCss('assets/src/css/app.css', 'css')
  .sourceMaps()
  .version();

// Fonts-Verzeichnis existiert nicht, daher kein Copy-Schritt

// Disable success notifications in development
if (!mix.inProduction()) {
  mix.disableSuccessNotifications();
}

const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')
    .addEntry('auth', './assets/js/auth.js')
    .addEntry('header', './assets/js/header.js')
    .addEntry('parameter', './assets/js/parameter.js')
    .addEntry('parameter/general', './assets/js/parameter/general.js')
    .addEntry('parameter/users', './assets/js/parameter/users.js')
    .addEntry('parameter/customers', './assets/js/parameter/customers.js')
    .addEntry('parameter/config', './assets/js/parameter/config.js')
    .addEntry('parameter_users', './assets/js/parameter/users.js')
    .addEntry('parameter_customers', './assets/js/parameter/customers.js')
    .addEntry('dashboard', './assets/js/dashboard.js')
    .addEntry('project', './assets/js/project.js')
    .addStyleEntry('styles', './assets/styles/style.scss')

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    // Commenté car le fichier controllers.json est manquant
    //.enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
        // Ajouter la configuration pour permettre les imports ES6
        config.sourceType = 'unambiguous';
    })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })

    // enables Sass/SCSS support
    .enableSassLoader()

    // uncomment if you use PostCSS/Autoprefixer
    .enablePostCssLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    .enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()

    // Copie les images depuis assets/images
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[hash:8].[ext]',
        pattern: /\.(png|jpg|jpeg|gif|ico|svg|webp)$/
    })
;

module.exports = Encore.getWebpackConfig(); 
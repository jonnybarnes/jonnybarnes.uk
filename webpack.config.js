const path = require('path');
const StyleLintPlugin = require('stylelint-webpack-plugin');
const CompressionPlugin = require('compression-webpack-plugin');
const zlib = require('zlib');
const EslintPlugin = require('eslint-webpack-plugin');

const config = {
    entry: ['./resources/js/app.js'],
    output: {
        path: path.resolve('./public/assets'),
        filename: 'app.js',
    },
    module: {
        rules: [{
            test: /\.css$/,
            exclude: /node_modules/,
            use: [
                {
                    loader: 'style-loader'
                },
                {
                    loader: 'css-loader',
                    options: {
                        sourceMap: process.env.NODE_ENV !== 'production'
                    }
                },
                {
                    loader: 'postcss-loader',
                    options: {
                        postcssOptions: {
                            config: path.resolve(__dirname, 'postcss.config.js'),
                        },
                        sourceMap: process.env.NODE_ENV !== 'production'
                    }
                }
            ]
        }, {
            test: /\.js$/,
            exclude: /node_modules/,
            use: {
                loader: 'babel-loader',
                options: {
                    presets: [
                        ['@babel/preset-env', { targets: "defaults" }]
                    ]
                }
            }
        }]
    },
    plugins: [
        new StyleLintPlugin({
            configFile: path.resolve(__dirname + '/.stylelintrc'),
            context: path.resolve(__dirname + '/resources/css'),
            files: '**/*.css',
        }),
        new EslintPlugin({
            context: path.resolve(__dirname + '/resources/js'),
            files: '**/*.js',
        }),
        new CompressionPlugin({
            filename: "[path][base].br",
            algorithm: "brotliCompress",
            test: /\.js$|\.css$/,
            exclude: /.map$/,
            compressionOptions: {
                params: {
                    [zlib.constants.BROTLI_PARAM_QUALITY]: 11,
                },
            },
        }),
    ]
};

module.exports = (env, argv) => {
    if (argv.mode === 'development') {
        config.devtool = 'eval-source-map';
    }

    return config;
};

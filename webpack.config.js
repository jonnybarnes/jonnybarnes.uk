const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const StyleLintPlugin = require('stylelint-webpack-plugin');
const CompressionPlugin = require('compression-webpack-plugin');
const zlib = require('zlib');

module.exports = {
    mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
    devtool: 'source-map',
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
                { loader: MiniCssExtractPlugin.loader },
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
        }]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'app.css',
            chunkFilename: 'app.css',
        }),
        new StyleLintPlugin({
            configFile: path.resolve(__dirname + '/.stylelintrc'),
            context: path.resolve(__dirname + '/resources/css'),
            files: '**/*.css',
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

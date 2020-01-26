const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
    devtool: 'source-map',
    entry: [
        './resources/sass/app.scss'
    ],
    output: {
        path: path.resolve('./public/assets'),
    },
    module: {
        rules: [{
            test: /\.scss$/,
            use: [{
                loader: MiniCssExtractPlugin.loader, options: {
                    sourceMap: true
                }
            }, {
                loader: 'css-loader', options: {
                    sourceMap: true
                }
            }, {
                loader: 'sass-loader', options: {
                    sourceMap: true,
                }
            }]
        }]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'app.css',
            chunkFilename: 'app.css',
        }),
    ]
};

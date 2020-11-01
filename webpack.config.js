const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const StyleLintPlugin = require('stylelint-webpack-plugin');

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
                {
                    loader: MiniCssExtractPlugin.loader,
                    options: {
                        sourceMap: true
                    }
                },
                {
                    loader: 'css-loader',
                    options: {
                        sourceMap: true
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
    ]
};

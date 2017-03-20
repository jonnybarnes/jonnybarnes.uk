const webpack = require('webpack');

const config = {
  context: __dirname + '/resources/assets/es6',
  entry: {
    //app: './app.js',
    links: './links.js',
    maps: './maps.js',
    newnote: './newnote.js'
  },
  output: {
    path: __dirname + '/public/assets/js',
    filename: '[name].js'
  },
  module: {
    loaders: [
      {
        test: /\.js$/,
        exclude: __dirname + '/node_modules/',
        loader: 'babel-loader'
      }
    ]
  }
};

module.exports = config;

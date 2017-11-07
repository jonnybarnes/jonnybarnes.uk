const webpack = require('webpack');
const Dotenv = require('dotenv-webpack');

const config = {
  context: __dirname + '/resources/assets/es6',
  entry: {
    //app: './app.js',
    links: './links.js',
    maps: './maps.js',
    newnote: './newnote.js',
    piwik: './piwik.js',
    places: './places.js',
    colours: './colours.js'
  },
  output: {
    path: __dirname + '/public/assets/js',
    filename: '[name].js'
  },
  devtool: 'source-map',
  module: {
    noParse: [/(mapbox-gl)\.js$/],
    loaders: [
      {
        test: /\.js$/,
        exclude: __dirname + '/node_modules/',
        loader: 'babel-loader'
      }
    ]
  },
  plugins: [
    new Dotenv({
      path: './.env'
    })
  ]
};

module.exports = config;

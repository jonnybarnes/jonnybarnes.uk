const webpack = require('webpack');
const Dotenv = require('dotenv-webpack');

const config = {
  context: __dirname + '/resources/es6',
  entry: {
    a11y: './a11y.js',
    colours: './colours.js',
    links: './links.js',
    maps: './maps.js',
    piwik: './piwik.js',
    places: './places.js'
  },
  output: {
    filename: '[name].js',
    path: __dirname + '/public/assets/js'
  },
  devtool: 'source-map',
  module: {
    noParse: [/mapbox-gl\.js$/],
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env'],
          }
        }
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

const path = require('path');
const webpack = require('webpack');
const TerserPlugin = require("terser-webpack-plugin");
const MiniCssExtractPlugin = require('mini-css-extract-plugin'); // Added import

module.exports = {
    optimization: {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                terserOptions: {
                    format: {
                        comments: false,
                    },
                },
                extractComments: false,
            }),
        ],
    },
    entry: ['./assets/timeTable.css', './assets/timeTable.js', './assets/timeTableApiHandler.js'],
    output: {
        path: path.resolve(__dirname, './dist/js/'),
        filename: 'timeTable.js',
    },
    plugins: [
        new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
        }),
        new MiniCssExtractPlugin({ filename: '../css/timeTable.css' }), // Added plugin configuration
    ],
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],  // updated rule to handle CSS files
            },
            // add additional rules for your project as needed
        ],
    },
    mode: 'production',
};

const path = require('path');
const webpack = require('webpack');

module.exports = {
    entry: ['./assets/timeTable.js', './assets/timeTableApiHandler.js'],
    output: {
        path: path.resolve(__dirname, './dist/js/'),
        filename: 'timeTable.js',
    },
    plugins: [
        new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
        }),
    ],
module: {
    rules: [
        {
            test: /\.css$/i,
            use: ["style-loader", "css-loader"],
    },
    ],
    },
    mode: 'production',
};

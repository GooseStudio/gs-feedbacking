/* webpack.config.js */
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');

module.exports = {
    plugins: [
        new MiniCssExtractPlugin({
            filename: "[name].css",
        }),
    ],
    // Tell webpack to begin building its
    // dependency graph from this file.
    entry: {
        'frontend/main': path.join(__dirname, 'assets-src', 'index.jsx'), // (your main JS)
        'frontend/style': './assets-src/feedback.sass', // (first css file)
        'admin/main': './assets-src/admin.js', // (another css file)
        'admin/style': './assets-src/admin.css', // (another css file)
    },
    // And to place the output in the `build` directory
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'dist'),
        hashFunction: "xxhash64"
    },
    module: {
        rules: [
            {
                test: /\.jsx?$/,
                /* We'll leave npm packages as is and not
                   parse them with Babel since most of them
                   are already pre-transpiled anyway. */
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env'],
                        plugins: [
                            '@babel/plugin-proposal-class-properties'
                        ]
                    },
                }
            }, {
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },
            {
                test: /\.sass$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    "css-loader",
                    {
                        loader: "sass-loader?sourceMap",
                        options: {
                            // Prefer `dart-sass`
                            implementation: require("sass"),
                        },
                    },
                ]
            }
        ]
    },
    devtool: "source-map",
    resolve: {
        modules:['.','node_modules'],
        extensions: ['.js', '.jsx']
    },
    externals: {
        jquery: 'jQuery',
        react: 'React',
        'react-dom': 'ReactDOM'
    }
};

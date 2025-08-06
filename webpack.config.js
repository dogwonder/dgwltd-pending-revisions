/**
 * Webpack Configuration
 *
 * @package DGW\PendingRevisions
 * @since 1.0.0
 */

const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        // Block Editor sidebar plugin
        sidebar: './src/index.ts',
        
        // Post edit enhancements
        'post-edit': './src/admin/post-edit.ts',
        
        // Meta box quick switch functionality
        'meta-box': './src/admin/meta-box.ts',
        
        // Settings page
        'settings': './src/admin/settings.ts',
        
        // Public facing scripts
        'public': './src/public/index.ts',
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js',
    },
    resolve: {
        ...defaultConfig.resolve,
        extensions: ['.tsx', '.ts', '.js', '.jsx'],
        alias: {
            '@': path.resolve(__dirname, 'src'),
        },
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.tsx?$/,
                use: [
                    {
                        loader: 'ts-loader',
                        options: {
                            configFile: 'tsconfig.json',
                            transpileOnly: true,
                        },
                    },
                ],
                exclude: /node_modules/,
            },
        ],
    },
    externals: {
        ...defaultConfig.externals,
        // Add any additional externals if needed
    },
};
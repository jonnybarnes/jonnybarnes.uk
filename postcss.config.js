module.exports = {
    plugins: {
        'postcss-import': {},
        'autoprefixer': {},
        '@csstools/postcss-oklab-function': {
            preserve: true
        },
        'postcss-nesting': {},
        'postcss-combine-media-query': {},
        'postcss-combine-duplicated-selectors': {
            removeDuplicatedProperties: true,
            removeDuplicatedValues: true
        },
        'cssnano': { preset: 'default' },
    }
};

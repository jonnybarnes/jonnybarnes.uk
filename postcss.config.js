module.exports = {
    plugins: {
        'postcss-import': {},
        'autoprefixer': {},
        'postcss-combine-media-query': {},
        'postcss-combine-duplicated-selectors': {
            removeDuplicatedProperties: true,
            removeDuplicatedValues: true
        },
        'cssnano': { preset: 'default' },
    }
};

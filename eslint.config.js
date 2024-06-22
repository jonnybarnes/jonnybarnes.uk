import js from '@eslint/js';
import stylistic from '@stylistic/eslint-plugin';
import globals from 'globals';

export default [
  {
    name: 'jonnybarnes-uk-config',
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser
      }
    },
    linterOptions: {
      reportUnusedDisableDirectives: 'error'
    },
    plugins: {
      '@stylistic': stylistic
    },
    rules: {
      ...js.configs.recommended.rules,
      '@stylistic/indent': ['error', 2],
      '@stylistic/linebreak-style': ['error', 'unix'],
      '@stylistic/quotes': ['error', 'single'],
      '@stylistic/semi': ['error', 'always'],
      'no-console': ['error', { allow: ['warn', 'error'] }],
      'no-await-in-loop': 'error',
      'no-promise-executor-return': 'error',
      'require-atomic-updates': 'error',
      'max-nested-callbacks': ['error', 3],
      'prefer-promise-reject-errors': 'error',
    },
  }
];

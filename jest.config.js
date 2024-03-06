/* eslint-disable @typescript-eslint/no-var-requires */
/* eslint-disable import/no-anonymous-default-export */
/*
 * For a detailed explanation regarding each configuration property and type check, visit:
 * https://jestjs.io/docs/configuration
 */
const { resolve } = require('path')
const coverageThreshold = require('./threshold-js.json')
const baseDir = __dirname
module.exports = {
  clearMocks: true,
  collectCoverage: true,
  coverageDirectory: 'coverage',
  coverageProvider: 'babel',
  testEnvironment: 'jsdom',
  moduleDirectories: ['./node_modules'],
  moduleNameMapper: {
    '\\.(css|less|scss|sss|styl)$': resolve(baseDir, 'node_modules', 'jest-css-modules'), // Equivalent of identity-obj-proxy
  },

  coverageThreshold: {
    global: coverageThreshold,
  },
}

// Polyfill TextEncoder/TextDecoder for jsdom (needed by react-router-dom v7)
const { TextEncoder, TextDecoder } = require('util');
Object.assign(globalThis, { TextEncoder, TextDecoder });

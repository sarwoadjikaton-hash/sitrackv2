/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/**/*.ts",
    "./resources/**/*.tsx",
  ],
  theme: {
    extend: {
      colors: {
        navy: {
          50: '#eef1fb',
          100: '#d8dff6',
          200: '#b3c0ed',
          400: '#5569c9',
          500: '#2743AF',
          600: '#1f37a0',
          700: '#1a2d7a',
          800: '#142060',
          900: '#0f1a4a',
        },
        accent: {
          400: '#5bb7fb',
          500: '#3DA5F9',
          600: '#2191f0',
        },
        'deep-navy': '#03205A',
        'trust-blue': '#1C386F',
        'teal-accent': '#167992',
        'soft-blue': '#B5CCE3',
        'powder-cyan': '#E4F5F9',
        'ice-white': '#EEF7FC',
      },
      fontFamily: {
        display: ['"Plus Jakarta Sans"', 'sans-serif'],
        body: ['Inter', 'sans-serif'],
        'mono-tracking': ['"JetBrains Mono"', '"Courier New"', 'monospace'],
      },
    },
  },
  plugins: [],
  corePlugins: {
    preflight: false,
  }
}

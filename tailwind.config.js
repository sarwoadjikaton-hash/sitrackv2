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
        'deep-navy': '#03205A',
        'trust-blue': '#1C386F',
        'teal-accent': '#167992',
        'soft-blue': '#B5CCE3',
        'powder-cyan': '#E4F5F9',
        'ice-white': '#EEF7FC',
      },
    },
  },
  plugins: [],
  corePlugins: {
    preflight: false,
  }
}

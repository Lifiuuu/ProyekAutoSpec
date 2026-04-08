module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.jsx',
  ],
  theme: {
    extend: {
      colors: {
        'dark-bg': '#1E1E1E',
        'primary-blue': '#1B3C53',
        'as-text': '#F7F8F0',
      },
      fontFamily: {
        sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'],
      },
    },
  },
  plugins: [],
};

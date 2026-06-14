/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.html'],
  theme: {
    extend: {
      colors: {
        mint: '#a9e2cc',
        lightyellow: '#fff4d6',
        cream: '#FEFAE0',
        warmyellow: '#ffda69',
        tan: '#E2C2A2',
      },
      fontFamily: {
        display: ['"Playfair Display"', 'Georgia', 'serif'],
        body: ['"Source Serif 4"', 'Georgia', 'serif'],
      },
    },
  },
};

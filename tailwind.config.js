/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.html'],
  theme: {
    extend: {
      colors: {
        'text-strong': '#3d3225',
        'text-body': '#5c4e3a',
        'text-muted': '#6f6047',
        'text-dark': '#1a2820',
        'text-footer': '#5c3d1e',
        'accent': '#5fa88a',
        'accent-dark': '#4a8a6e',
        'dark': '#1a2820',
        mint: '#a9e2cc',
        'mint-dark': '#8ed4b8',
        'tan-dark': '#d4b391',
        'mint-soft': '#d4f1e6',
        'mint-light': '#f0faf6',
        'tan-pale': '#fffaf0',
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

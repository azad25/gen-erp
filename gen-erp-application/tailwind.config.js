import defaultTheme from 'tailwindcss/defaultTheme'

export default {
  content: ['./resources/**/*.blade.php','./resources/**/*.vue','./resources/**/*.js'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary:    { DEFAULT: '#0F766E', dark: '#0D6B63', light: '#14B8A6' },
        accent:     '#14B8A6',
        success:    '#16A34A', warning: '#CA8A04', danger: '#B91C1C',
        bodybg:     '#FAFAFA',
        boxdark:    '#0D1F1E',    // sidebar background
        'boxdark-2':'#162221',   // sidebar dropdown
        stroke:     '#E5E7EB',   'gray-1': '#6B7280',
        'gray-2':   '#D1D5DB',   'gray-3': '#F3F4F6',
        whiten:     '#F1F5F9',   black: '#1F2937', 'black-2': '#374151',
      },
      fontFamily: {
        satoshi: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
        jakarta: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
        bangla:  ['"Noto Sans Bengali"', '"Plus Jakarta Sans"', 'sans-serif'],
        mono:    ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
      },
    },
  },
  plugins: [],
}

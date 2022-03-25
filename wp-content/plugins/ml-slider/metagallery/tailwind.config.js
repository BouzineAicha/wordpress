module.exports = {
    important: true,
    purge: {
        enabled: process.env.BUILD === 'production',
        content: ['resources/views/**/*.php', 'resources/js/**/*.js'],
        options: {
            safelist: [/bg-nord*/],
        },
    },
    darkMode: 'class',
    theme: {
        fontFamily: {
            heading: ['Rubik', 'sans-serif'],
            body: ['"IBM Plex Sans"', 'sans-serif'],
        },
        screens: {
            sm: '600px',
            md: '782px',
            lg: '961px',
            xl: '1280px',
            '2xl': '1536px',
        },
        colors: {
            white: '#ffffff',
            nord0: '#2E3440',
            nord1: '#3B4252',
            nord2: '#434C5E',
            nord3: '#4C566A',
            nord4: '#D8DEE9',
            nord5: '#E5E9F0',
            nord6: '#ECEFF4',
            nord7: '#8FBCBB',
            nord8: '#88C0D0',
            nord9: '#81A1C1',
            nord10: '#5E81AC',
            nord11: '#BF616A',
            nord12: '#D08770',
            nord13: '#EBCB8B',
            nord14: '#A3BE8C',
            nord15: '#B48EAD',
        },
        extend: {
            zIndex: {
                high: '10000000',
                max: '2147483647',
            },
        },
    },
    variants: {
        extend: {
            padding: ['focus'],
            ringColor: ['hover'],
        },
    },
    plugins: [],
}

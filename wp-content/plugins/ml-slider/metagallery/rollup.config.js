import json from '@rollup/plugin-json'
import babel from '@rollup/plugin-babel'
import commonjs from '@rollup/plugin-commonjs'
import resolve from '@rollup/plugin-node-resolve'
import virtual from '@rollup/plugin-virtual'
import postcss from 'rollup-plugin-postcss'
import filesize from 'rollup-plugin-filesize'
import { terser } from 'rollup-plugin-terser'
import path from 'path'
import fs from 'fs'

const createConfig = ([filename, filelocation]) => ({
    input: fs.existsSync(`${filelocation}.js`) ? path.resolve(`${filelocation}.js`) : `${filename}`,
    output: [
        {
            file: `public/build/${filename}.js`,
            format: 'umd',
            name: `${filename}`,
        },
    ],
    external: false,
    treeshake: {
        propertyReadSideEffects: false,
    },
    plugins: [
        virtual({
            [`${filename}`]: `${filelocation}`,
        }),
        babel({
            babelHelpers: 'bundled',
            exclude: 'node_modules/**',
        }),
        resolve({
            mainFields: ['module', 'jsnext', 'main'],
            browser: true,
            extensions: ['.mjs', '.js', '.jsx', '.json', '.node'],
            preferBuiltins: false,
        }),
        commonjs({
            include: /\/node_modules\//,
        }),
        postcss({
            extensions: ['.css'],
            extract: path.resolve(`public/build/${filename}.css`),
            plugins: [
                require('postcss-import'),
                require('tailwindcss'),
                require('postcss-nested'),
                require('autoprefixer'),
                process.env.BUILD === 'production' &&
                    require('cssnano')({
                        preset: 'default',
                    }),
            ],
        }),
        json(),
        filesize(),
        {
            writeBundle(options) {
                // Remove empty chunks
                fs.existsSync(`${filelocation}.js`) || fs.unlinkSync(options.file)
            },
        },
        process.env.BUILD === 'production' && terser(),
    ],
})

export default Object.entries({
    metagallery: 'resources/js/metagallery',
    'metagallery-scripts': 'resources/js/public',
    theme: `import metagallery from '${path.resolve('resources/css/metagallery.css')}'`,
    'metagallery-styles': `import metagallery from '${path.resolve('resources/css/public.css')}'`,
}).map(createConfig)

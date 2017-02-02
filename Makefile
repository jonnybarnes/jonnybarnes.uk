# A Makefile to run various tasks

.PHONY: sass frontend js compress lint-sass lint-js
jsfiles := $(wildcard resources/assets/js/*.js)
sassfiles := $(wildcard resources/assets/sass/*.scss)
yarnfiles:= node_modules/mapbox-gl/dist/mapbox-gl.css
assets := public/assets/css/app.css \
public/assets/prism/prism.css public/assets/prism/prism.js \
$(wildcard public/assets/js/*.js) \
$(wildcard public/assets/frontend/*.css)

sass: public/assets/css/app.css

public/assets/css/app.css: lint-sass
	sassc --style compressed --sourcemap resources/assets/sass/app.scss public/assets/css/app.css
	postcss --use autoprefixer --autoprefixer.browsers "> 5%" --output public/assets/css/app.css public/assets/css/app.css

frontend: $(yarnfiles)
	for f in $^; do \
		cp $$f public/assets/frontend/`basename $$f`; \
	done;

js: $(jsfiles)
	for f in $^; do \
		uglifyjs $$f --screw-ie8 --compress --mangle --source-map public/assets/js/`basename $$f`.map --output public/assets/js/`basename $$f`; \
	done;

compress: $(assets)
	for f in $^; do \
		zopfli $$f; \
		bro --force --quality 11 --input $$f --output $$f.br; \
	done;

lint-sass: $(sassfiles)
	for f in $^; do \
		stylelint --syntax=scss $$f; \
	done;

lint-js: $(jsfiles)
	for f in $^; do \
		eslint $$f; \
	done;

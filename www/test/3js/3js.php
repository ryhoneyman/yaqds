<!DOCTYPE html>
<html lang="en">
	<head>
		<title>three.js webgl - animation - keyframes</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
		<!--<link type="text/css" rel="stylesheet" href="main.css">-->
		<style>
			body {
				background-color: #bfe3dd;
				color: #000;
			}

			a {
				color: #2983ff;
			}
		</style>
	</head>

	<body>

		<div id="container"></div>

		<script type="importmap">
			{
				"imports": {
				      "three": "https://cdn.jsdelivr.net/npm/three@0.164.1/build/three.module.js",
				      "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.164.1/examples/jsm/"
				}
    			}
		</script>

		<script type="module">

import * as THREE from 'three';
import { GUI } from 'three/addons/libs/lil-gui.module.min.js';
			import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
			import { SVGLoader } from 'three/addons/loaders/SVGLoader.js';

			let renderer, scene, camera, gui, guiData;

			init();

			//

			function init() {

				const container = document.getElementById( 'container' );

				//

				camera = new THREE.PerspectiveCamera( 50, window.innerWidth / window.innerHeight, 1, 1000 );
				camera.position.set( 0, 0, 200 );

				//

				renderer = new THREE.WebGLRenderer( { antialias: true } );
				renderer.setPixelRatio( window.devicePixelRatio );
				renderer.setSize( window.innerWidth, window.innerHeight );
				container.appendChild( renderer.domElement );

				//

				const controls = new OrbitControls( camera, renderer.domElement );
				controls.addEventListener( 'change', render );
				controls.screenSpacePanning = true;

				//

				window.addEventListener( 'resize', onWindowResize );

				guiData = {
					currentURL: 'map2.svg',
					drawFillShapes: true,
					drawStrokes: true,
					fillShapesWireframe: false,
					strokesWireframe: false
				};

				loadSVG( guiData.currentURL );

				createGUI();

			}

			function createGUI() {

				if ( gui ) gui.destroy();

				gui = new GUI();

				gui.add( guiData, 'currentURL', {

					'Tiger': 'models/svg/tiger.svg',
					'Joins and caps': 'models/svg/lineJoinsAndCaps.svg',
					'Hexagon': 'models/svg/hexagon.svg',
					'Energy': 'models/svg/energy.svg',
					'Test 1': 'models/svg/tests/1.svg',
					'Test 2': 'models/svg/tests/2.svg',
					'Test 3': 'models/svg/tests/3.svg',
					'Test 4': 'models/svg/tests/4.svg',
					'Test 5': 'models/svg/tests/5.svg',
					'Test 6': 'models/svg/tests/6.svg',
					'Test 7': 'models/svg/tests/7.svg',
					'Test 8': 'models/svg/tests/8.svg',
					'Test 9': 'models/svg/tests/9.svg',
					'Units': 'models/svg/tests/units.svg',
					'Ordering': 'models/svg/tests/ordering.svg',
					'Defs': 'models/svg/tests/testDefs/Svg-defs.svg',
					'Defs2': 'models/svg/tests/testDefs/Svg-defs2.svg',
					'Defs3': 'models/svg/tests/testDefs/Wave-defs.svg',
					'Defs4': 'models/svg/tests/testDefs/defs4.svg',
					'Defs5': 'models/svg/tests/testDefs/defs5.svg',
					'Style CSS inside defs': 'models/svg/style-css-inside-defs.svg',
					'Multiple CSS classes': 'models/svg/multiple-css-classes.svg',
					'Zero Radius': 'models/svg/zero-radius.svg',
					'Styles in svg tag': 'models/svg/tests/styles.svg',
					'Round join': 'models/svg/tests/roundJoinPrecisionIssue.svg',
					'Ellipse Transformations': 'models/svg/tests/ellipseTransform.svg',
					'singlePointTest': 'models/svg/singlePointTest.svg',
					'singlePointTest2': 'models/svg/singlePointTest2.svg',
					'singlePointTest3': 'models/svg/singlePointTest3.svg',
					'emptyPath': 'models/svg/emptyPath.svg',

				} ).name( 'SVG File' ).onChange( update );

				gui.add( guiData, 'drawStrokes' ).name( 'Draw strokes' ).onChange( update );

				gui.add( guiData, 'drawFillShapes' ).name( 'Draw fill shapes' ).onChange( update );

				gui.add( guiData, 'strokesWireframe' ).name( 'Wireframe strokes' ).onChange( update );

				gui.add( guiData, 'fillShapesWireframe' ).name( 'Wireframe fill shapes' ).onChange( update );

				function update() {

					loadSVG( guiData.currentURL );

				}

			}

			function loadSVG( url ) {

				//

				scene = new THREE.Scene();
				scene.background = new THREE.Color( 0xb0b0b0 );

				//

				const helper = new THREE.GridHelper( 160, 10, 0x8d8d8d, 0xc1c1c1 );
				helper.rotation.x = Math.PI / 2;
				scene.add( helper );

				//

				const loader = new SVGLoader();

				loader.load( url, function ( data ) {

					const group = new THREE.Group();
					group.scale.multiplyScalar( 0.25 );
					group.position.x = - 70;
					group.position.y = 70;
					group.scale.y *= - 1;

					let renderOrder = 0;

					for ( const path of data.paths ) {

						const fillColor = path.userData.style.fill;

						if ( guiData.drawFillShapes && fillColor !== undefined && fillColor !== 'none' ) {

							const material = new THREE.MeshBasicMaterial( {
								color: new THREE.Color().setStyle( fillColor ),
								opacity: path.userData.style.fillOpacity,
								transparent: true,
								side: THREE.DoubleSide,
								depthWrite: false,
								wireframe: guiData.fillShapesWireframe
							} );

							const shapes = SVGLoader.createShapes( path );

							for ( const shape of shapes ) {

								const geometry = new THREE.ShapeGeometry( shape );
								const mesh = new THREE.Mesh( geometry, material );
								mesh.renderOrder = renderOrder ++;

								group.add( mesh );

							}

						}

						const strokeColor = path.userData.style.stroke;

						if ( guiData.drawStrokes && strokeColor !== undefined && strokeColor !== 'none' ) {

							const material = new THREE.MeshBasicMaterial( {
								color: new THREE.Color().setStyle( strokeColor ),
								opacity: path.userData.style.strokeOpacity,
								transparent: true,
								side: THREE.DoubleSide,
								depthWrite: false,
								wireframe: guiData.strokesWireframe
							} );

							for ( const subPath of path.subPaths ) {

								const geometry = SVGLoader.pointsToStroke( subPath.getPoints(), path.userData.style );

								if ( geometry ) {

									const mesh = new THREE.Mesh( geometry, material );
									mesh.renderOrder = renderOrder ++;

									group.add( mesh );

								}

							}

						}

					}

					scene.add( group );

					render();

				} );

			}

			function onWindowResize() {

				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();

				renderer.setSize( window.innerWidth, window.innerHeight );
				render();

			}

			function render() {

				renderer.render( scene, camera );

			}


		</script>

	</body>

</html>

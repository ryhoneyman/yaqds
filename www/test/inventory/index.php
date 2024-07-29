<html>
<head>
<style data-tippy-stylesheet="">.tippy-box[data-animation=fade][data-state=hidden]{opacity:0}[data-tippy-root]{max-width:calc(100vw - 10px)}.tippy-box{position:relative;background-color:#333;color:#fff;border-radius:4px;font-size:14px;line-height:1.4;white-space:normal;outline:0;transition-property:transform,visibility,opacity}.tippy-box[data-placement^=top]>.tippy-arrow{bottom:0}.tippy-box[data-placement^=top]>.tippy-arrow:before{bottom:-7px;left:0;border-width:8px 8px 0;border-top-color:initial;transform-origin:center top}.tippy-box[data-placement^=bottom]>.tippy-arrow{top:0}.tippy-box[data-placement^=bottom]>.tippy-arrow:before{top:-7px;left:0;border-width:0 8px 8px;border-bottom-color:initial;transform-origin:center bottom}.tippy-box[data-placement^=left]>.tippy-arrow{right:0}.tippy-box[data-placement^=left]>.tippy-arrow:before{border-width:8px 0 8px 8px;border-left-color:initial;right:-7px;transform-origin:center left}.tippy-box[data-placement^=right]>.tippy-arrow{left:0}.tippy-box[data-placement^=right]>.tippy-arrow:before{left:-7px;border-width:8px 8px 8px 0;border-right-color:initial;transform-origin:center right}.tippy-box[data-inertia][data-state=visible]{transition-timing-function:cubic-bezier(.54,1.5,.38,1.11)}.tippy-arrow{width:16px;height:16px;color:#333}.tippy-arrow:before{content:"";position:absolute;border-color:transparent;border-style:solid}.tippy-content{position:relative;padding:5px 9px;z-index:1}</style>
<script src="https://www.pqdi.cc/static/color-modes.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://www.pqdi.cc/static/style.css">
<script src="https://www.pqdi.cc/static/popper.min.js"></script>
<script src="https://www.pqdi.cc/static/tippy-bundle.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        tippy('.tooltip-link', {
            content: 'Loading...',
            allowHTML: true,
            maxWidth: 'none',
            onShow(instance) {
                const url = instance.reference.dataset.url;
                fetch(`https://www.pqdi.cc/get-item-tooltip/${encodeURIComponent(url)}`)
                    .then(response => response.text())
                    .then(content => {
                        instance.setContent(content);
                    })
                    .catch(error => {
                        console.error('Error fetching tooltip content:', error);
                    });
            },
        });
    });
</script>
</head>
<body class="h-100 text-white">
<svg width="442" height="419" xmlns="http://www.w3.org/2000/svg">
   <image href="/images/inventory.png"/>
   <svg id="legs" x="192" y="265">
        <image href="/images/icons/item_540.png" data-url="3282" class="tooltip-link link" width="36"/>
   </svg>
   <svg id="feet" x="230" y="265">
        <image href="/images/icons/item_633.png" data-url="1206" class="tooltip-link link" width="36"/>
   </svg>
</svg>
</body>
</html>
<meta name="aether-ui" content="0.2.2">
<style id="aether-ui-tokens">{!! $tokens !!}</style>
<style id="aether-ui-styles">{!! file_get_contents(plugin_path('aether-ui', 'resources/css/aether.css')) !!}</style>
<script>
{!! file_get_contents(plugin_path('aether-ui', 'resources/js/aether.js')) !!}
window.AetherUI.mount({!! json_encode($browserConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!});
</script>

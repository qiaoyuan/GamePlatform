<script>
export default {
  name: 'MenuItem',
  functional: true,
  props: {
    icon: {
      type: String,
      default: ''
    },
    title: {
      type: String,
      default: ''
    }
  },
  render(h, context) {
    const { icon, title } = context.props
    const vnodes = []

    if (icon) {
      if (!icon || icon.indexOf('#') === -1) {
        vnodes.push(<i class={icon}/>)
      }
    }

    if (title) {
      if (!icon || icon.indexOf('#') === -1) {
        vnodes.push(<span slot='title'>{(title)}</span>)
      } else {
        vnodes.push(<span class="pl10" slot='title'>{(title)}</span>)
      }
    }
    return vnodes
  }
}
</script>

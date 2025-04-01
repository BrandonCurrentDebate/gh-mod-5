(($) => {

  const {
    select,
    input,
    inputRepeater,
    loadingModal,
    toggle,
    dialog,
    inputWithReplacements,
  } = Groundhogg.element

  const {
    metaPicker,
  } = Groundhogg.pickers

  const {
    wcProductPicker,
    wcTagPicker,
    wcCategoryPicker,
    wcTagsStore,
    wcCategoriesStore,
    wcProductsStore,
  } = Groundhogg.WooCommerce

  const { sprintf, __, _x, _n } = wp.i18n

  const renderComponent = (id, step, component) => {

    const mount = (step) => {
      $(`#${id}`).html(component.edit(step))

      component.onMount(step, (meta, reRender) => {

        let step = Funnel.updateStepMeta(meta)

        if (reRender) {
          mount(step)
        }

      }, () => {}, () => Funnel.getActiveStep())
    }

    mount(step)

  }

  const FunnelSteps = {

    init () {

      $(document).on('step-active', async e => {

        let active = Funnel.getActiveStep()

        let product_types = [
          'wc_purchase',
          'wc_add_to_cart',
          'wc_new_order',
          'wc_order_updated',
          'wc_cart_abandoned',
        ]

        if (product_types.includes(active.data.step_type)) {

          let productPickerId = `step_${active.ID}_products`
          let tagPickerId = `step_${active.ID}_tags`
          let categoryPickerId = `step_${active.ID}_categories`

          wcProductPicker(`#${productPickerId}`, {
            placeholder: __('Any product'),
          })

          wcTagPicker(`#${tagPickerId}`, {
            placeholder: __('Any tag'),
          })

          wcCategoryPicker(`#${categoryPickerId}`, {
            placeholder: __('Any category'),
          })
        }
      })
    },

  }

  FunnelSteps.init()

})(jQuery)

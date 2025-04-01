(($) => {

  const { createStore } = Groundhogg

  const {
    apiPicker
  } = Groundhogg.pickers

  const { order_statuses, rest_route } = GroundhoggWoocommerce

  const storeOverrides = {
    primaryKey: 'id',
    getItemFromResponse: (r) => r,
    getItemsFromResponse: (r) => r,
  }

  const wcCategoriesStore = createStore('woocommerce-categories', `${rest_route}/products/categories`, storeOverrides)
  const wcTagsStore = createStore('woocommerce-tags', `${rest_route}/products/tags`, storeOverrides)
  const wcProductsStore = createStore('woocommerce-products', `${rest_route}/products`, storeOverrides)

  const wcPicker = (selector, store, ...opts) => {
    return apiPicker(selector, store.route, true, false,
      (r) => {
        store.itemsFetched(r)
        return r.map(p => ({
          id: p.id,
          text: p.name
        }))
      },
      (q) => ({
        search: q.term,
        per_page: 20
      }), ...opts)
  }

  const productSelect = (props, selected) => {
    return select(props, wcProductsStore.getItems().map(p => ({
      value: parseInt(p.id),
      text: p.name
    })), selected)
  }

  const productTagsSelect = (props, selected) => {
    return select(props, wcTagsStore.getItems().map(p => ({
      value: parseInt(p.id),
      text: p.name
    })), selected)
  }

  const productCategoriesSelect = (props, selected) => {
    return select(props, wcCategoriesStore.getItems().map(p => ({
      value: parseInt(p.id),
      text: p.name
    })), selected)
  }

  const wcProductPicker = (selector, ...opts) => {
    return wcPicker(selector, wcProductsStore, ...opts)
  }

  const wcTagPicker = (selector, ...opts) => {
    return wcPicker(selector, wcTagsStore, ...opts)
  }

  const wcCategoryPicker = (selector, ...opts) => {
    return wcPicker(selector, wcCategoriesStore, ...opts)
  }

  Groundhogg.WooCommerce = {
    wcProductPicker,
    wcTagPicker,
    wcCategoryPicker,
    wcTagsStore,
    wcCategoriesStore,
    wcProductsStore
  }

})(jQuery)
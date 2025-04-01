( ($) => {

  const { createStore } = Groundhogg

  const { select, input, bold, orList } = Groundhogg.element

  const {
    createFilters,
    registerFilter,
    registerActivityFilter,
    registerFilterGroup,
    ComparisonsTitleGenerators,
    NumericComparisons,
    standardActivityDateOptions,
    standardActivityDateTitle,
    standardActivityDateDefaults,
    standardActivityDateFilterOnMount,
    registerActivityFilterWithValue
  } = Groundhogg.filters.functions

  const { __, _n, sprintf, _x } = wp.i18n

  const { order_statuses } = GroundhoggWoocommerce

  const {
    wcProductPicker,
    wcTagPicker,
    wcCategoryPicker,
    wcTagsStore,
    wcCategoriesStore,
    wcProductsStore,
  } = Groundhogg.WooCommerce

  registerFilterGroup('woocommerce', __('WooCommerce'))

  registerActivityFilterWithValue('woocommerce_order_activity', 'woocommerce', __('Order Activity', 'groundhogg'), {
    view () {
      return __('Placed an order')
    },
  })

  registerFilter('woocommerce_product_purchased', 'woocommerce', __('Purchased Product', 'groundhogg-wc'), {
    view ({ product_ids, ...props }) {

      let products = product_ids.length ? orList(product_ids.map(p => bold(wcProductsStore.get(p).name))) : __(
        '<b>any product</b>')

      return standardActivityDateTitle(
        sprintf(__('Purchased %s', 'groundhogg-wc'), products), props)
    },
    edit ({ product_ids, ...props }) {
      // language=html
      return `
          ${ select({
              id: 'filter-product-id',
              name: 'product_ids',
              multiple: true,
          }, wcProductsStore.getItems().map(c => ( {
              text: c.name,
              value: c.id,
          } )), product_ids) } ${ standardActivityDateOptions(props) }`
    },
    onMount (filter, updateFilter) {

      wcProductPicker('#filter-product-id', {
        placeholder: __('Please select one or more products', 'groundhogg-wc'),
      }).on('change', (e) => {
        updateFilter({
          product_ids: $(e.target).val().map(id => parseInt(id)),
        })
      })

      standardActivityDateFilterOnMount(filter, updateFilter)

    },
    defaults: {
      product_ids: [],
      ...standardActivityDateDefaults,
    },
    preload: ({ product_ids }) => {
      return wcProductsStore.fetchItems({
        include: product_ids,
      })
    },
  })

  registerFilter('woocommerce_product_purchased_in_category', 'woocommerce',
    __('Purchased Product in Category', 'groundhogg-wc'), {
      view ({ taxonomies, ...props }) {

        taxonomies = taxonomies.length ? orList(taxonomies.map(id => bold(wcCategoriesStore.get(id).name))) : __('<b>any category</b>')

        return standardActivityDateTitle(sprintf( __('Purchased products in %s', 'groundhogg-wc'), taxonomies), props);
      },
      edit ({ taxonomies, ...props }) {
        // language=html
        return `
            ${ select({
                id: 'filter-taxonomies',
                name: 'taxonomies',
                multiple: true,
            }, wcCategoriesStore.getItems().map(c => ( {
                text: c.name,
                value: c.id,
            } )), taxonomies) } ${ standardActivityDateOptions(props) }`
      },
      onMount (filter, updateFilter) {

        wcCategoryPicker('#filter-taxonomies', {
          placeholder: __('Please select one or more categories', 'groundhogg-wc'),
        }).on('change', (e) => {
          updateFilter({
            taxonomies: $(e.target).val().map(id => parseInt(id)),
          })
        })

        standardActivityDateFilterOnMount(filter, updateFilter)

      },
      defaults: {
        taxonomies: [],
        ...standardActivityDateDefaults,
      },
      preload: ({ taxonomies }) => {
        return wcCategoriesStore.fetchItems({
          include: taxonomies,
        })
      },
    })

  registerFilter('woocommerce_product_purchased_with_tag', 'woocommerce',
    __('Purchased Product with Tag', 'groundhogg-wc'), {
      view ({ taxonomies, ...props }) {

        taxonomies = taxonomies.length ? orList(taxonomies.map(id => bold(wcTagsStore.get(id).name))) : __('<b>any tag</b>')

        return standardActivityDateTitle(sprintf(
          __('Purchased products with %s', 'groundhogg-wc'), taxonomies ), props)
      },
      edit ({ taxonomies, ...props }) {
        // language=html
        return `
            ${ select({
                id: 'filter-taxonomies',
                name: 'taxonomies',
                multiple: true,
            }, wcTagsStore.getItems().map(c => ( {
                text: c.name,
                value: c.id,
            } )), taxonomies) } ${ standardActivityDateOptions(props) }`
      },
      onMount (filter, updateFilter) {

        wcTagPicker('#filter-taxonomies', {
          placeholder: __('Please select one or more tags', 'groundhogg-wc'),
        }).on('change', (e) => {
          updateFilter({
            taxonomies: $(e.target).val().map(id => parseInt(id)),
          })
        })

        standardActivityDateFilterOnMount(filter, updateFilter)

      },
      defaults: {
        taxonomies: [],
        ...standardActivityDateDefaults,
      },
      preload: ({ taxonomies }) => {
        return wcTagsStore.fetchItems({
          include: taxonomies,
        })
      },
    })

  registerFilter('woocommerce_customer_value', 'woocommerce', __('Lifetime Customer Value', 'groundhogg-wc'), {
    view ({ compare, value }) {
      return ComparisonsTitleGenerators[compare](`<b>${ __('Customer value', 'groundhogg-wc') }</b>`,
        `<b>"${ value }"</b>`)
    },
    edit ({ compare, value }) {
      // language=html
      return `
          ${ select({
              id: 'filter-compare',
              name: 'compare',
          }, NumericComparisons, compare) } ${ input({
              type: 'number',
              id: 'filter-value',
              name: 'value',
              value,
          }) }`
    },
    onMount (filter, updateFilter) {

      $('#filter-compare, #filter-value').on('change', function (e) {
        const $el = $(this)
        const { compare } = updateFilter({
          [$el.prop('name')]: $el.val(),
        })
      })
    },
    defaults: {
      compare: 'equals',
      value: '',
    },
  })

  registerFilter('woocommerce_order_count', 'woocommerce', __('Number of Orders', 'groundhogg-wc'), {
    view ({ compare, value }) {
      return ComparisonsTitleGenerators[compare](`<b>${ __('Order count', 'groundhogg-wc') }</b>`,
        `<b>"${ value }"</b>`)
    },
    edit ({ compare, value }) {
      // language=html
      return `
          ${ select({
              id: 'filter-compare',
              name: 'compare',
          }, NumericComparisons, compare) } ${ input({
              type: 'number',
              id: 'filter-value',
              name: 'value',
              value,
          }) }`
    },
    onMount (filter, updateFilter) {

      $('#filter-compare, #filter-value').on('change', function (e) {
        const $el = $(this)
        const { compare } = updateFilter({
          [$el.prop('name')]: $el.val(),
        })
      })
    },
    defaults: {
      compare: 'equals',
      value: '',
    },
  })

} )(jQuery)

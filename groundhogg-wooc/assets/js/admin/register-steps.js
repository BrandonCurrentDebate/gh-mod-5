(function ($) {

  const {
    select,
    loadingDots,
    input,
    andList,
    orList,
    specialChars,
    toggle,
    uuid,
    inputWithReplacements
  } = Groundhogg.element

  const {
    post,
    get,
    routes
  } = Groundhogg.api

  const {
    registerStepType,
    registerStepPack,
    updateCurrentStepMeta,
    renderStepEdit,
    getCurrentStep,
    getPrecedingSteps,
    getProceedingSteps,
    stepTitle,
    slot
  } = Groundhogg.funnelEditor.functions

  const {
    apiPicker
  } = Groundhogg.pickers

  const storeOverrides = {
    primaryKey: 'id',
    getItemFromResponse: (r) => r,
    getItemsFromResponse: (r) => r,
  }

  const ProductCategoriesStore = Groundhogg.createStore('wCategories', `${GroundhoggWC.rest_route}/products/categories`, storeOverrides)
  const ProductTagsStore = Groundhogg.createStore('wcTags', `${GroundhoggWC.rest_route}/products/tags`, storeOverrides)
  const ProductsStore = Groundhogg.createStore('wcProducts', `${GroundhoggWC.rest_route}/products`, storeOverrides)

  const wcPicker = (selector, store) => {
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
      })
    )
  }

  const productSelect = (props, selected) => {
    return select(props, ProductsStore.getItems().map(p => ({
      value: parseInt(p.id),
      text: p.name
    })), selected)
  }

  const productTagsSelect = (props, selected) => {
    return select(props, ProductTagsStore.getItems().map(p => ({
      value: parseInt(p.id),
      text: p.name
    })), selected)
  }

  const productCategoriesSelect = (props, selected) => {
    return select(props, ProductCategoriesStore.getItems().map(p => ({
      value: parseInt(p.id),
      text: p.name
    })), selected)
  }

  const wcProductPicker = (selector) => {
    return wcPicker(selector, ProductsStore)
  }

  const wcTagPicker = (selector) => {
    return wcPicker(selector, ProductTagsStore)
  }

  const wcCategoryPicker = (selector) => {
    return wcPicker(selector, ProductCategoriesStore)
  }

  const addToCartConditions = {
    any: 'Any product is added to the cart',
    tags: 'A product with the following tags is added to the cart',
    categories: 'A product in the following categories is added to the cart',
    products: 'Any of the following products are added to the cart',
  }

  const purchasedConditions = {
    any: 'Any product is purchased',
    tags: 'A product with the following tags is purchased',
    categories: 'A product in the following categories is purchased',
    products: 'Any of the following products are purchased',
  }

  const wcStepOnMount = (updateStepMeta) => {
    wcProductPicker('#products').on('change', (e) => {
      updateStepMeta({
        products: $(e.target).val().map(p => parseInt(p))
      })
    })

    wcTagPicker('#tags').on('change', (e) => {
      updateStepMeta({
        tags: $(e.target).val().map(p => parseInt(p))
      })
    })

    wcCategoryPicker('#categories').on('change', (e) => {
      updateStepMeta({
        categories: $(e.target).val().map(p => parseInt(p))
      })
    })
  }

  const wcPreloadStep = ({ meta }) => {
    const {
      products = [],
      tags = [],
      categories = [],
    } = meta

    const promises = []

    if (products.length > 0) {
      promises.push(ProductsStore.fetchItems({
        include: products
      }))
    }

    if (tags.length > 0) {
      promises.push(ProductTagsStore.fetchItems({
        include: tags
      }))
    }

    if (categories.length > 0) {
      promises.push(ProductCategoriesStore.fetchItems({
        include: categories
      }))
    }

    return promises
  }

  registerStepPack('woocommerce', 'WooCommerce',
    // language=HTML
    `
		<svg xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid" viewBox="0 0 256 153">
			<path
				d="M23.759 0h208.38c13.187 0 23.863 10.675 23.863 23.863v79.542c0 13.187-10.675 23.863-23.863 23.863h-74.727l10.257 25.118-45.109-25.118H23.865c-13.187 0-23.863-10.675-23.863-23.863V23.863C-.103 10.78 10.573 0 23.76 0z"
				fill="#7f54b3"/>
			<path
				d="M14.578 21.75c1.457-1.977 3.642-3.018 6.556-3.226 5.307-.416 8.325 2.081 9.054 7.493 3.226 21.75 6.764 40.169 10.51 55.259l22.79-43.395c2.082-3.955 4.684-6.036 7.806-6.244 4.579-.312 7.388 2.601 8.533 8.741 2.602 13.841 5.932 25.6 9.886 35.59 2.706-26.433 7.285-45.476 13.737-57.236 1.561-2.913 3.85-4.37 6.868-4.579 2.394-.208 4.58.52 6.557 2.082 1.977 1.56 3.018 3.538 3.226 5.931.104 1.874-.209 3.434-1.041 4.995-4.059 7.493-7.389 20.085-10.094 37.567-2.602 16.964-3.538 30.18-2.914 39.65.208 2.601-.208 4.89-1.249 6.868-1.248 2.29-3.122 3.538-5.515 3.746-2.706.208-5.515-1.04-8.221-3.85-9.678-9.886-17.38-24.663-22.998-44.332-6.764 13.32-11.76 23.31-14.985 29.97-6.14 11.76-11.343 17.796-15.714 18.108-2.81.208-5.203-2.186-7.285-7.18-5.307-13.634-11.03-39.962-17.17-78.986-.417-2.705.207-5.099 1.664-6.972zm223.64 16.338c-3.746-6.556-9.262-10.511-16.65-12.072-1.977-.416-3.85-.624-5.62-.624-9.99 0-18.107 5.203-24.455 15.61-5.41 8.845-8.117 18.628-8.117 29.346 0 8.013 1.665 14.88 4.995 20.605 3.747 6.556 9.262 10.51 16.65 12.072 1.978.416 3.85.624 5.62.624 10.094 0 18.211-5.203 24.455-15.61 5.411-8.95 8.117-18.732 8.117-29.45.104-8.117-1.665-14.881-4.995-20.501zm-13.112 28.826c-1.457 6.868-4.059 11.967-7.909 15.402-3.018 2.706-5.827 3.85-8.43 3.33-2.497-.52-4.578-2.706-6.139-6.764-1.249-3.226-1.873-6.452-1.873-9.47 0-2.602.208-5.203.728-7.597.937-4.266 2.706-8.429 5.516-12.384 3.434-5.099 7.076-7.18 10.823-6.452 2.497.52 4.579 2.706 6.14 6.765 1.248 3.226 1.873 6.452 1.873 9.47 0 2.705-.208 5.307-.729 7.7zm-52.033-28.826c-3.746-6.556-9.366-10.511-16.65-12.072-1.977-.416-3.85-.624-5.62-.624-9.99 0-18.107 5.203-24.455 15.61-5.41 8.845-8.117 18.628-8.117 29.346 0 8.013 1.665 14.88 4.995 20.605 3.747 6.556 9.262 10.51 16.65 12.072 1.978.416 3.85.624 5.62.624 10.094 0 18.211-5.203 24.455-15.61 5.411-8.95 8.117-18.732 8.117-29.45 0-8.117-1.665-14.881-4.995-20.501zm-13.216 28.826c-1.457 6.868-4.059 11.967-7.909 15.402-3.018 2.706-5.828 3.85-8.43 3.33-2.497-.52-4.578-2.706-6.139-6.764-1.249-3.226-1.873-6.452-1.873-9.47 0-2.602.208-5.203.728-7.597.937-4.266 2.706-8.429 5.516-12.384 3.434-5.099 7.076-7.18 10.823-6.452 2.497.52 4.579 2.706 6.14 6.765 1.248 3.226 1.873 6.452 1.873 9.47.104 2.705-.208 5.307-.729 7.7z"
				fill="#fff"/>
		</svg>`
  )

  registerStepType('wc_add_to_cart', {
    pack: 'woocommerce',
    // language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -64 512 512">
			<path fill="currentColor"
			      d="M435.996 166.363l19.043-80.855c2.293-9.738-5.125-18.985-15.031-18.985H113.305l-4.676-20.277C102.352 19.016 78.457 0 50.516 0H15.44C6.918 0 0 6.918 0 15.441c0 8.528 6.918 15.446 15.441 15.446h35.075c13.468 0 24.996 9.172 28.023 22.297.05.214 50.723 220.039 50.848 220.566v38.059c0 24.367 19.824 44.195 44.195 44.195h62.89c8.524 0 15.442-6.918 15.442-15.442 0-8.527-6.918-15.445-15.441-15.445h-62.891c-7.344 0-13.312-5.969-13.312-13.308v-24.38h130.023c7.535 54.141 54.14 95.95 110.32 95.95 61.414 0 111.387-49.973 111.387-111.39 0-49.055-31.883-90.802-76.004-105.626zm-35.387 186.13c-44.484 0-80.504-36.216-80.504-80.505 0-44.285 36.04-80.496 80.504-80.496 44.395 0 80.508 36.113 80.508 80.496 0 44.39-36.117 80.504-80.508 80.504zM420.512 97.41l-14.918 63.313c-58.426-2.555-107.547 40.117-115.301 95.824H157.117C129.657 137.48 137.773 172.69 120.43 97.41zm0 0"/>
			<path fill="currentColor"
			      d="M204.703 160.32h131.625c8.527 0 15.445-6.906 15.445-15.441 0-8.524-6.918-15.441-15.445-15.441H204.703c-8.535 0-15.445 6.917-15.445 15.44 0 8.536 6.91 15.442 15.445 15.442zm0 0M204.703 218.742h62.734c8.524 0 15.442-6.906 15.442-15.441 0-8.524-6.918-15.442-15.441-15.442h-62.735c-8.535 0-15.445 6.918-15.445 15.442 0 8.535 6.91 15.441 15.445 15.441zm0 0M446.41 256.547h-30.355v-30.36c0-8.523-6.918-15.44-15.446-15.44-8.554 0-15.441 6.937-15.441 15.44v30.36h-30.36c-8.535 0-15.44 6.918-15.44 15.441 0 8.535 6.905 15.442 15.44 15.442h30.36v30.37c0 8.524 6.918 15.442 15.441 15.442s15.442-6.918 15.442-15.441V287.43h30.36c8.534 0 15.44-6.907 15.44-15.442.004-8.523-6.906-15.441-15.44-15.441zm0 0"/>
		</svg>`,

    title ({ meta }) {

      const {
        products = [],
        tags = [],
        categories = [],
        condition = 'any'
      } = meta

      switch (condition) {
        default:
        case 'any':
          return `<b>Any product</b> is added to the cart`
        case 'products':
          return `${orList(products.map(p => `<b>${ProductsStore.get(p).name}</b>`))} is added to the cart`
        case 'tags':
          return `Products with ${orList(tags.map(p => `<b>${ProductTagsStore.get(p).name}</b>`))} tag is added to the cart`
        case 'categories':
          return `Products in ${orList(categories.map(p => `<b>${ProductCategoriesStore.get(p).name}</b>`))} is added to the cart`
      }

    },

    edit ({ meta }) {

      const {
        products = [],
        tags = [],
        categories = [],
        condition = 'any'
      } = meta

      // language=HTML
      return `
		  <div class="panel">
			  <div class="row">
				  <label class="row-label">Run when...</label>
				  ${select({
					  id: 'condition',
					  name: 'condition'
				  }, addToCartConditions, condition)}
			  </div>
			  <div class="row">
				  ${condition === 'products' ? `<label class="row-label">Select products...</label>
				  ${productSelect({
					  id: 'products',
					  name: 'products',
					  multiple: true
				  }, products)}` : ''}
				  ${condition === 'tags' ? `<label class="row-label">Select product tags...</label>
				  ${productTagsSelect({
					  id: 'tags',
					  name: 'tags',
					  multiple: true
				  }, tags)}` : ''}
				  ${condition === 'categories' ? `<label class="row-label">Select product categories...</label>
				  ${productCategoriesSelect({
					  id: 'categories',
					  name: 'categories',
					  multiple: true
				  }, categories)}` : ''}
			  </div>
		  </div>`
    },

    onMount (step, updateStepMeta) {

      $('#condition').on('change', (e) => {
        updateStepMeta({
          condition: e.target.value
        }, true)
      })

      wcStepOnMount(updateStepMeta)

    },

    preload (step) {
      return wcPreloadStep(step)
    }

  })

  registerStepType('wc_purchase', {
    pack: 'woocommerce',

    // language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 511.998 511.998">
			<path
				fill="currentColor"
				d="M354.099 399.601l-27.218-30.785-22.475 19.872 48.725 55.108 78.919-81.747-21.583-20.836z"/>
			<path
				fill="currentColor"
				d="M369.451 265.331c-2.735 0-5.447.099-8.138.275l-8.698-148.885h-70.979v-28.95C281.635 39.374 242.261 0 193.864 0s-87.771 39.374-87.771 87.771v28.95h-70.98L19.347 386.594c-1.227 21.013 6.051 41.018 20.494 56.329s33.989 23.743 55.037 23.743H273.99c22.636 27.652 57.021 45.332 95.461 45.332 68.006 0 123.333-55.327 123.333-123.333 0-68.007-55.327-123.334-123.333-123.334zM136.092 87.771c0-31.855 25.916-57.771 57.771-57.771s57.772 25.916 57.772 57.771v28.95H136.092zM94.878 436.666c-12.702 0-24.498-5.089-33.214-14.329s-13.108-21.313-12.368-33.993l14.115-241.622h42.681v37.588h30v-37.588h115.543v37.588h30v-37.588h42.681l7.277 124.562c-49.541 16.016-85.475 62.578-85.475 117.382 0 17.017 3.465 33.24 9.725 48.001H94.878zm274.573 45.332c-51.464 0-93.333-41.869-93.333-93.333 0-51.465 41.869-93.334 93.333-93.334s93.333 41.869 93.333 93.334c0 51.464-41.869 93.333-93.333 93.333z"/>
		</svg>`,

    title ({ meta }) {

      const {
        products = [],
        tags = [],
        categories = [],
        condition = 'any'
      } = meta

      switch (condition) {
        default:
        case 'any':
          return `<b>Any product</b> is purchased`
        case 'products':
          return `${orList(products.map(p => `<b>${ProductsStore.get(p).name}</b>`))} is purchased`
        case 'tags':
          return `Products with ${orList(tags.map(p => `<b>${ProductTagsStore.get(p).name}</b>`))} tag is purchased`
        case 'categories':
          return `Products in ${orList(categories.map(p => `<b>${ProductCategoriesStore.get(p).name}</b>`))} is purchased`
      }

    },

    edit ({ meta }) {

      const {
        products = [],
        tags = [],
        categories = [],
        condition = 'any'
      } = meta

      // language=HTML
      return `
		  <div class="panel">
			  <div class="row">
				  <label class="row-label">Run when...</label>
				  ${select({
					  id: 'condition',
					  name: 'condition'
				  }, purchasedConditions, condition)}
			  </div>
			  <div class="row">
				  ${condition === 'products' ? `<label class="row-label">Select products...</label>
				  ${productSelect({
					  id: 'products',
					  name: 'products',
					  multiple: true
				  }, products)}` : ''}
				  ${condition === 'tags' ? `<label class="row-label">Select product tags...</label>
				  ${productTagsSelect({
					  id: 'tags',
					  name: 'tags',
					  multiple: true
				  }, tags)}` : ''}
				  ${condition === 'categories' ? `<label class="row-label">Select product categories...</label>
				  ${productCategoriesSelect({
					  id: 'categories',
					  name: 'categories',
					  multiple: true
				  }, categories)}` : ''}
			  </div>
		  </div>`
    },

    onMount (step, updateStepMeta) {

      $('#condition').on('change', (e) => {
        updateStepMeta({
          condition: e.target.value
        }, true)
      })

      wcStepOnMount(updateStepMeta)

    },

    preload (step) {
      return wcPreloadStep(step)
    }

  })

  registerStepType('wc_order_updated', {
    pack: 'woocommerce',
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 511.998 511.998">
			<path fill="currentColor"
			      d="M369.451 265.331c-2.735 0-5.447.099-8.138.275l-8.698-148.885h-70.979v-28.95C281.635 39.374 242.261 0 193.864 0s-87.771 39.374-87.771 87.771v28.95h-70.98L19.347 386.594c-1.227 21.013 6.051 41.018 20.494 56.329s33.989 23.743 55.037 23.743H273.99c22.636 27.652 57.021 45.332 95.461 45.332 68.006 0 123.333-55.327 123.333-123.333 0-68.007-55.327-123.334-123.333-123.334zM136.092 87.771c0-31.855 25.916-57.771 57.771-57.771s57.772 25.916 57.772 57.771v28.95H136.092zM94.878 436.666c-12.702 0-24.498-5.089-33.214-14.329s-13.108-21.313-12.368-33.993l14.115-241.622h42.681v37.588h30v-37.588h115.543v37.588h30v-37.588h42.681l7.277 124.562c-49.541 16.016-85.475 62.578-85.475 117.382 0 17.017 3.465 33.24 9.725 48.001H94.878zm274.573 45.332c-51.464 0-93.333-41.869-93.333-93.333 0-51.465 41.869-93.334 93.333-93.334s93.333 41.869 93.333 93.334c0 51.464-41.869 93.333-93.333 93.333z"/>
			<g>
				<path fill="currentColor"
				      d="M341.169 441.1c-.136 2.05.441 4.13 1.661 5.659a8.2 8.2 0 005.04 2.975c.547.095 1.123.13 1.685.115l.017-.001 34.93-.97c4.994-.14 9.326-4.292 9.676-9.277.35-4.984-3.415-8.912-8.41-8.773l-13.11.364 58.097-55.69c3.776-3.62 4.177-9.336.895-12.76-3.281-3.423-9.01-3.264-12.785.356l-58.096 55.69.941-13.421c.35-4.985-3.415-8.913-8.41-8.774-4.995.139-9.327 4.292-9.676 9.276l-2.471 35.214c.017 0 .008.009.016.017z"/>
				<path fill="currentColor"
				      d="M349.17 341.859c1.521 1.588 3.71 2.537 6.207 2.459l13.45-.374-58.096 55.691c-3.776 3.62-4.177 9.336-.895 12.76s9.01 3.263 12.785-.356l58.096-55.691-.918 13.082c-.174 2.483.673 4.718 2.195 6.306s3.71 2.537 6.208 2.459c4.994-.139 9.327-4.292 9.676-9.276l.195-2.773c.159-2.263.334-4.526.476-6.788.196-2.917.4-5.826.614-8.744.218-2.875.344-5.756.589-8.641.23-2.791.416-5.572.468-8.367.05-2.904-1.082-5.547-3.52-7.05a7.758 7.758 0 00-2.088-.918c-2.181-.559-4.65-.304-6.942-.248-2.794.06-5.592.172-8.397.258-3.333.101-6.673.177-10.005.261-2.814.096-5.644.174-8.466.261-1.364.038-2.718.067-4.082.105l-.068.002c-4.995.139-9.327 4.292-9.677 9.276-.184 2.492.673 4.718 2.195 6.306z"/>
			</g>
		</svg>`,
    title ({ meta }) {

      const {
        order_status = [],
        products = [],
        tags = [],
        categories = [],
        gateways = []
      } = meta

      if (!order_status || order_status.length === 0) {
        return 'Order status is changed.'
      }

      return `Order status changed to ${orList(order_status.map(s => `<b>${GroundhoggWC.order_statuses[s]}</b>`))}`
    },
    edit ({ meta }) {

      const {
        order_status = [],
        products = [],
        tags = [],
        categories = [],
        gateways = []
      } = meta

      //language=HTML
      return `
		  <div class="panel">
			  <div class="row">
				  <label class="row-label">Run when the status is changed to...</label>
				  ${select({
					  id: 'order-status',
					  name: 'order_status',
					  multiple: true,
				  }, GroundhoggWC.order_statuses, order_status)}
			  </div>
			  <div class="row">
				  <label class="row-label">Filter by products...</label>
				  ${productSelect({
					  id: 'products',
					  name: 'products',
					  multiple: true
				  }, products)}
			  </div>
			  <div class="row">
				  <label class="row-label">Filter by product categories...</label>
				  ${productCategoriesSelect({
					  id: 'categories',
					  name: 'categories',
					  multiple: true
				  }, categories)}
			  </div>
			  <div class="row">
				  <label class="row-label">Filter by product tags...</label>
				  ${productTagsSelect({
					  id: 'tags',
					  name: 'tags',
					  multiple: true,
				  }, tags)}
			  </div>
			  <div class="row">
				  <label class="row-label">Filter by payment gateway...</label>
				  ${select({
					  id: 'gateways',
					  name: 'gateways',
					  multiple: true,
				  }, GroundhoggWC.gateways, gateways)}
			  </div>
		  </div>`
    },
    onMount (step, updateStepMeta) {
      $('#order-status').select2().on('change', (e) => {
        updateStepMeta({
          order_status: $(e.target).val()
        })
      })

      $('#gateways').select2().on('change', (e) => {
        updateStepMeta({
          gateways: $(e.target).val()
        })
      })

      wcStepOnMount(updateStepMeta)
    },
    onDemount () {},
    preload (step) {
      return wcPreloadStep(step)
    }
  })

  registerStepType('wc_emptied_cart', {
    pack: 'woocommerce',

    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
			<path fill="currentColor"
			      d="M204.688 224.572H336.34c8.508 0 15.39-6.894 15.39-15.39s-6.882-15.391-15.39-15.391H204.687c-8.496 0-15.39 6.895-15.39 15.39s6.894 15.391 15.39 15.391zm0 0M204.688 283.006h62.753c8.497 0 15.391-6.887 15.391-15.39 0-8.497-6.894-15.391-15.39-15.391h-62.754c-8.497 0-15.391 6.894-15.391 15.39 0 8.504 6.894 15.39 15.39 15.39zm0 0"/>
			<path fill="currentColor"
			      d="M435.969 230.705l19.066-80.922c2.274-9.672-5.09-18.922-14.98-18.922h-326.82l-4.688-20.324c-6.281-27.21-30.156-46.215-58.074-46.215H15.39C6.895 64.322 0 71.217 0 79.712s6.895 15.392 15.39 15.392h35.083c13.492 0 25.043 9.183 28.07 22.347 11.297 48.988 39.484 171.27 50.863 220.621v38.078c0 24.336 19.801 44.153 44.153 44.153h62.906c8.496 0 15.39-6.899 15.39-15.395 0-8.504-6.894-15.39-15.39-15.39h-62.906c-7.368 0-13.371-6-13.371-13.368V351.71h130.164c7.511 54.145 54.113 95.969 110.289 95.969 61.402 0 111.359-49.961 111.359-111.36 0-49.054-31.89-90.804-76.031-105.613zM400.64 416.893c-44.454 0-80.575-36.18-80.575-80.575 0-44.402 36.13-80.574 80.575-80.574 44.336 0 80.578 36.067 80.578 80.574 0 44.43-36.149 80.575-80.578 80.575zm19.968-255.25l-14.937 63.441c-57.047-2.797-107.442 39.062-115.32 95.844H157.047l-36.723-159.285H420.61zm0 0"/>
			<path fill="currentColor"
			      d="M447.726 337.5c-.04-7.444-6.096-13.545-13.539-13.642-7.588-.093-67.021-.846-81.098-1.027a13.19 13.19 0 00-13.404 13.305c.026 7.4 6.084 13.546 13.54 13.64l81.097 1.028a13.19 13.19 0 0013.404-13.305zm0 0"/>
		</svg>`,
    title () {
      return 'Cart is emptied.'
    },
    edit () {
      //language=HTML
      return `
		  <div class="panel">
			  <div class="row">
				  <p>Runs whenever a customer empties their cart. This step does not have any settings.</p>
			  </div>
		  </div>`
    },
    onMount () {},
    onDemount () {},
  })

  registerStepType('wc_reached_checkout', {
    pack: 'woocommerce',
    //language=HTML
    svg: `
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="-40 0 512 512">
			<path fill="currentColor"
			      d="M390.871 141.938H346.06v-23.825h61.949c8.285 0 15-6.715 15-15V15c0-8.285-6.715-15-15-15h-217.48c-8.286 0-15 6.715-15 15v88.113c0 8.285 6.714 15 15 15h61.949v23.825h-92.465v-41.579c0-8.285-6.715-15-15-15H68.129c-8.281 0-15 6.715-15 15v41.579H42.113C18.891 141.938 0 160.827 0 184.05V467c0 24.813 20.188 45 45 45h342.984c24.813 0 45-20.188 45-45V184.05c0-23.222-18.89-42.113-42.113-42.113zM30 184.05c0-6.68 5.434-12.113 12.113-12.113h11.02v36.714c0 8.286 6.715 15 15 15h76.883c8.28 0 15-6.714 15-15v-36.714H390.87c6.68 0 12.113 5.433 12.113 12.113V389.18H30zm100.012-68.692v78.293H83.129V115.36zM205.527 30h187.48v58.113h-187.48zm110.528 88.113v23.825h-33.578v-23.825zM402.985 467c0 8.27-6.731 15-15 15H45c-8.27 0-15-6.73-15-15v-47.82h372.984zm0 0"/>
			<path fill="currentColor"
			      d="M358.262 195.664H194.953c-8.281 0-15 6.719-15 15 0 8.285 6.719 15 15 15h163.309c8.285 0 15-6.715 15-15 0-8.281-6.715-15-15-15zm0 0M214.21 242.242h-19.257c-8.281 0-15 6.719-15 15 0 8.285 6.719 15 15 15h19.258c8.281 0 15-6.715 15-15 0-8.281-6.719-15-15-15zm0 0M286.234 242.242H266.98c-8.285 0-15 6.719-15 15 0 8.285 6.715 15 15 15h19.254c8.286 0 15-6.715 15-15 0-8.281-6.714-15-15-15zm0 0M358.262 242.242h-19.254c-8.285 0-15 6.719-15 15 0 8.285 6.715 15 15 15h19.254c8.285 0 15-6.715 15-15 0-8.281-6.715-15-15-15zm0 0M214.21 284.96h-19.257c-8.281 0-15 6.716-15 15 0 8.282 6.719 15 15 15h19.258c8.281 0 15-6.718 15-15 0-8.284-6.719-15-15-15zm0 0M286.234 284.96H266.98c-8.285 0-15 6.716-15 15 0 8.282 6.715 15 15 15h19.254c8.286 0 15-6.718 15-15 0-8.284-6.714-15-15-15zm0 0M358.262 284.96h-19.254c-8.285 0-15 6.716-15 15 0 8.282 6.715 15 15 15h19.254c8.285 0 15-6.718 15-15 0-8.284-6.715-15-15-15zm0 0M214.21 327.676h-19.257c-8.281 0-15 6.719-15 15 0 8.285 6.719 15 15 15h19.258c8.281 0 15-6.715 15-15 0-8.281-6.719-15-15-15zm0 0M286.234 327.676H266.98c-8.285 0-15 6.719-15 15 0 8.285 6.715 15 15 15h19.254c8.286 0 15-6.715 15-15 0-8.281-6.714-15-15-15zm0 0M358.262 327.676h-19.254c-8.285 0-15 6.719-15 15 0 8.285 6.715 15 15 15h19.254c8.285 0 15-6.715 15-15 0-8.281-6.715-15-15-15zm0 0M173.398 465.59h83.997c8.285 0 15-6.715 15-15 0-8.281-6.715-15-15-15h-83.997c-8.285 0-15 6.719-15 15 0 8.285 6.715 15 15 15zm0 0M358.262 74.059c8.285 0 15-6.72 15-15 0-8.286-6.715-15-15-15H238.316c-8.285 0-15 6.714-15 15 0 8.28 6.715 15 15 15zm0 0"/>
		</svg>`,
    title () {
      return 'Reached the checkout page'
    },
    edit () {
      //language=HTML
      return `
		  <div class="panel">
			  <div class="row">
				  <p>Runs whenever a customer lands on the checkout page. This step does not have any settings.</p>
			  </div>
		  </div>`
    },
    onMount () {},
    onDemount () {},
  })

  Groundhogg.woocommerce = {
    ProductsStore,
    ProductCategoriesStore,
    ProductTagsStore,
    productSelect,
    productTagsSelect,
    productCategoriesSelect,
    wcProductPicker,
    wcTagPicker,
    wcCategoryPicker,
    wcPreloadStep,
    wcStepOnMount,
    ...GroundhoggWC
  }

})(jQuery)
( () => {

  const {
    registerBlock,
    registerDynamicBlock,
    getActiveBlock,
    isActiveBlock,
    isEditing,
    isCreating,
    isBlockEditor,
    isHTMLEditor,
    components,
  } = Groundhogg.emailEditor

  const {
    Control,
    ControlGroup,
    ColorPicker,
    NumberControl,

  } = components

  const {
    Div,
    Fragment,
    makeEl,
    Img,
    Pg,
    Input,
    InputWithReplacements,
    Select,
  } = MakeEl

  const {
    base64_json_encode,
    debounce,
  } = Groundhogg.functions

  registerBlock('products', 'Products', {

  } )

  registerBlock('cart', 'Cart', {
    attributes: {},
    //language=HTML
    svg      : `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 13V9m9-3-2-2m-9-2h4m-2 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z"/>
        </svg>`,
    controls : ({
      date,
      time,
      theme,
      delay_type,
      updateBlock,
      clockFontColor = '',
      accentColor = '',
      backgroundColor = '',
      labelColor = '',
      delay_string = '',
      days = 7,
    }) => {

      const debounceUpdate = debounce(updateBlock, 500)

      return Fragment([
        ControlGroup({
          name: 'Countdown',
        }, [
          Control({ label: 'Type' }, Select({
            selected: delay_type,
            options : {
              evergreen: 'Evergreen',
              fixed    : 'Fixed',
              advanced    : 'Advanced',
            },
            onChange: e => updateBlock({ delay_type: e.target.value, morphControls: true }),
          })),
          delay_type === 'fixed' ? Control({ label: 'Date' }, Input({
            type    : 'date',
            value   : date,
            onChange: e => updateBlock({ date: e.target.value }),
          })) : null,
          delay_type === 'fixed' ? Control({ label: 'Time' }, Input({
            type    : 'time',
            value   : time,
            onChange: e => debounceUpdate({ time: e.target.value }),
          })) : null,
          delay_type === 'evergreen' ? Control({ label: 'Delay' }, NumberControl({
            id      : 'delay-days',
            unit    : 'days',
            value   : days,
            onChange: e => debounceUpdate({ days: e.target.value }),
          })) : null,
          delay_type === 'advanced' ? Control({ label: 'Delay', stacked: true }, InputWithReplacements({
            name    : 'delay_string',
            id      : 'delay-string',
            value   : delay_string,
            style: {
              flexGrow: 1
            },
            placeholder: 'A strtotime() friendly string',
            onChange: e => debounceUpdate({ delay_string: e.target.value }),
          })) : null,
          Control({ label: 'Theme' }, Select({
            selected: theme,
            options : CountdownTimer.themes,
            onChange: e => debounceUpdate({ theme: e.target.value }),
          })),
          Pg({}, `Have <a href="#" class="feedback-modal" data-subject="Countdown Timer">feedback</a> on this feature?` ),
          `<hr/>`,
          Control({ label: 'Clock Font Color' }, ColorPicker({
            id      : 'clock-font-color',
            value   : clockFontColor,
            onChange: clockFontColor => debounceUpdate({ clockFontColor }),
          })),
          Control({ label: 'Label Color' }, ColorPicker({
            id      : 'label-color',
            value   : labelColor,
            onChange: labelColor => debounceUpdate({ labelColor }),
          })),
          Control({ label: 'Accent Color' }, ColorPicker({
            id      : 'accent-color',
            value   : accentColor,
            onChange: accentColor => debounceUpdate({ accentColor }),
          })),
          Control({ label: 'Background Color' }, ColorPicker({
            id      : 'background-color',
            value   : backgroundColor,
            onChange: backgroundColor => debounceUpdate({ backgroundColor }),
          })),
        ]),
      ])
    },
    html     : ({
      date,
      time,
      theme,
      delay_type,
      clockFontColor = '',
      accentColor = '',
      backgroundColor = '',
      labelColor = '',
      days = 7,
      delay_string = '',
    }) => {

      let gifProps = {
        clockFontColor,
        accentColor,
        backgroundColor,
        labelColor,
        theme,
      }

      switch (delay_type) {
        default:
        case 'fixed':
          gifProps.date = date
          gifProps.time = time
          break
        case 'evergreen':
          gifProps.days = days

          break
        case 'advanced':
          gifProps.delay_string = delay_string
          break
      }

      return Img({
        src: `${ CountdownTimer.base_url }${ base64_json_encode(gifProps) }.gif/`,
        alt: 'Countdown Timer',
        style: {
          verticalAlign: 'middle'
        }
      })
    },
    plainText: () => '',
    defaults : {
      delay_type  : 'evergreen', // evergreen or date
      date        : moment().add(7, 'days').format('YYYY-MM-DD'),
      time        : '09:00:00',
      theme       : 'basic',
      days        : 7,
      delay_string: '+7 days',
    },
  })

} )()

( () => {

  const {
    ModalFrame,
    Div,
    Pg,
    An,
    Input,
    Button,
    Dashicon,
    H1,
    H2,
    H3,
    Fragment,
    Accordion,
    Ol,
    Span,
    Li,
    makeEl,
    Img,
  } = MakeEl

  const {
    icons,
    dialog,
  } = Groundhogg.element

  const {
    cron_download_url = '#',
  } = GroundhoggCron

  const State = Groundhogg.createState({
    ...GroundhoggCron,
  })

  const { ajax } = Groundhogg.api

  const {
    __,
    sprintf,
  } = wp.i18n

  const HOSTNAME = ( new URL(Groundhogg.url.home) ).hostname

  const copyText = url => {
    navigator.clipboard.writeText(url)
    dialog({
      message: 'Copied to clipboard!',
    })
  }

  const CopyInput = text => Div({ className: 'gh-input-group' }, [
    Input({
      value    : text,
      readonly : true,
      className: 'code full-width',
      onClick  : e => {
        e.target.select()
        copyText(text)
      },
    }),
    Button({
      className: 'gh-button icon secondary',
      onClick  : e => {
        copyText(text)
      },
    }, icons.duplicate),
  ])

  const SetupInstructions = (file) => Accordion({
    id      : `${ file.replaceAll('.', '-') }-instructions`,
    outlined: true,
    items   : [
      {
        title  : 'Setup using Cron-Job.org',
        content: () => Fragment([
          Pg({}, __('Cron-Job.org is a free service that we trust to keep your site running smoothly.', 'groundhogg')),
          Ol({}, [
            Li({}, __('Create an account on <a href="https://cron-job.org">Cron-Job.org</a>.', 'groundhogg')),
            Li({}, __('Once logged in, click <b>CREATE CRONJOB</b>.', 'groundhogg')),
            Li({}, [
              __('Copy the following into the <b>URL</b> field.', 'groundhogg'),
              CopyInput(`${ Groundhogg.url.home }/${ file }`),
            ]),
            Li({}, __('Set the <b>Execution Schedule</b> to "Every <b>1</b> minutes(s)".', 'groundhogg')),
            Li({}, __('Finish by clicking <b>CREATE</b>.', 'groundhogg')),
          ]),
        ]),
      },
      {
        title  : 'Setup using CPanel',
        content: () => Fragment([
          Pg({},
            __('Many hosts use CPanel for backend hosting administration. If your server uses CPanel you can use its built-in Cron Jobs tool.', 'groundhogg')),
          Ol({}, [
            Li({}, __('Once logged into your CPanel open the Cron Jobs tool.', 'groundhogg')),
            Li({}, __('Under <b>Add New Cron Job</b> select <code>Once Per Minute(*****)</code> from the <b>Common Settings</b> dropdown.', 'groundhogg')),
            Li({}, [
              __('Copy the following into the <b>Command</b> field.', 'groundhogg'),
              CopyInput(`/usr/local/bin/php /path/to/public_html/${ file } >/dev/null 2>&1`),
            ]),
            Li({}, __('Replace <code>/path/to/public_html/</code> with the real file location.', 'groundhogg')),
            Li({}, __('Finish by clicking <b>Add New Cron Job</b>.', 'groundhogg')),
          ]),
        ]),
      },
      {
        title  : 'Setup using SiteGround',
        content: () => Fragment([
          Pg({}, __(
            'SiteGround provides their own proprietary Cron Job tool which you should use for best results. If you have difficulties please open a ticket with SiteGround for assistance.',
            'groundhogg')),
          Ol({}, [
            Li({}, sprintf(__(
                'Login to your <a href="https://login.siteground.com/login">SiteGround account</a> and click on <b>Site Tools</b> for the site where %s is installed.',
                'groundhogg'),
              Groundhogg.whiteLabelName)),
            Li({}, __('Click <b>DEVS</b> in the left-hand menu, then <b>Cron Jobs</b> in the expanded menu.', 'groundhogg')),
            Li({}, [
              __('Under <b>Create New Cron Job</b>, copy the following into the <b>Command</b> field.', 'groundhogg'),
              CopyInput(`php /home/customer/www/${ HOSTNAME }/public_html/${ file } /dev/null 2>&1`),
            ]),
            Li({},
              sprintf(__('Confirm the file location %s is correct.', 'groundhogg'), `<code>/home/customer/www/${ HOSTNAME }/public_html/${ file }</code>`)),
            Li({}, sprintf(__('Enter %s into the <b>Interval</b> field.', 'groundhogg'), `<code>* * * * *</code>`)),
            Li({}, __('Finish by clicking <b>Create</b>.', 'groundhogg')),
          ]),
        ]),
      },
      {
        title  : 'Setup using Cloudways',
        content: () => Fragment([
          Pg({}, __(
            'Cloudways provides their own proprietary Cron Job tool which you should use for best results. If you have difficulties please open a ticket with Cloudways for assistance.',
            'groundhogg')),
          Ol({}, [
            Li({}, __('Login to your <a href="https://login.siteground.com/login">Cloudways account</a> and navigate to <b>Applications</b>.', 'groundhogg')),
            Li({}, sprintf(__('Open the application that has %s installed, then click <b>Cron Job Management</b> in the left-hand menu.', 'groundhogg'),
              Groundhogg.whiteLabelName)),
            Li({}, __('Click on the <b>Advanced</b> tab, then click the <b>ADD NEW CRON JOB</b> button.', 'groundhogg')),
            Li({}, __('Then select <code>Every minute(* * * * *)</code> from the <b>Common Settings</b> dropdown.', 'groundhogg')),
            Li({}, sprintf(__('Enter %s into the <b>Command</b> field.', 'groundhogg'), `<code>${ file }</code>`)),
            Li({}, __('Finish by clicking <b>SUBMIT</b>.', 'groundhogg')),
          ]),
        ]),
      },
      {
        title  : 'Setup using Kinsta',
        content: () => Fragment([
          Pg({}, __(
            'Kinsta does not provide a dedicated UI to manage cron jobs. Instead, you must ask Kinsta support to add them for you, or follow <a href="https://kinsta.com/docs/wordpress-hosting/site-management/cron-jobs/">Kinsta\'s guide on adding cron jobs via the command line</a>.',
            'groundhogg')),
          Pg({}, sprintf(__(
              'If you are asking Kinsta support for help, ask them to "Create a PHP cron job to run the %s file in the WordPress root directory every minute."',
              'groundhogg'),
            `<code>${ file }</code>`)),
        ]),
      },
      {
        title  : 'Generic setup instructions',
        content: () => Fragment([
          Pg({}, [
            __('For any other method, you essentially want a script to call the URL below <b>once every minute</b>.', 'groundhogg'),
            CopyInput(`${ Groundhogg.url.home }/${ file }`),
          ]),
        ]),
      },
    ],
  })

  const wpCronOnTime = () => State.wp_last_ping_diff < ( 60 * 15 )
  const ghCronOnTime = () => State.gh_last_ping_diff < 60

  let morphFunc

  setInterval(() => ajax({
    action: 'gh_check_cron',
  }).then(r => {
    if (r.success) {
      State.set({
        ...r.data,
      })
      morphFunc && morphFunc()
    }
  }), 15 * 1000)

  const CronJobSetup = () => {
    return Div({
      id   : 'cron-setup',
      style: {
        marginTop: '20px',
      },
    }, morph => {

      morphFunc = morph

      return Div({
        className: 'display-grid gap-20',
      }, [

        Groundhogg.isWhiteLabeled || GroundhoggCron.promo_dismissed ? null : Div({ className: 'full gh-site' }, Div({
          className: 'gh-primary-card display-flex align-center',
          style: {
            position: 'relative',
          }
        },[
          Div({
            style: {
              padding: '2rem'
            }
          },[
            Button({
              id: 'dismiss-cron-ad',
              className: 'gh-button secondary icon text',
              style: {
                position: 'absolute',
                top: '5px',
                right: '5px',
              },
              onClick: e => ajax({ action: 'gh_dismiss_notice', notice: 'gh-promo-cron-job' }).then( () => {
                GroundhoggCron.promo_dismissed = true
                morph()
              } )
            }, Dashicon( 'no-alt' ) ),
            H3({}, 'Do cron jobs give you <i>anxiety</i>?' ),
            Pg({}, 'Let our team help you set them up correctly! First time customers can have our team install Groundhogg, import contacts, connect SMTP, and set up cron jobs!'),
            Pg({}, An( { href: 'https://www.groundhogg.io/downloads/initial-setup-installation', className: 'bold' }, 'MORE DETAILS &rarr;' ) ),
          ]),
          Img({ src: `${Groundhogg.assets.images}/phil-cutoff.png` })
        ])),

        // Disable internal cron
        Div({ className: 'gh-panel half' }, Div({ className: 'inside' }, [
          H3({ className: `no-margin-top ${ State.gh_disable_wp_cron || State.DISABLE_WP_CRON ? 'striked-completed' : '' }` },
            Span({}, __('Disable the WordPress Internal Cron', 'groundhogg'))),
          Pg({}, __(
            'The internal WordPress cron system sends an asynchronous request to the <code>wp-cron.php</code> on every page load. This can slow down your site and create a poor experience for site visitors. We recommend disabling it. You will replace it with an external cron job which is much faster and better for performance.',
            'groundhogg')),
          Button({
            className: 'gh-button primary',
            id: 'disable-internal-cron',
            disabled : State.gh_disable_wp_cron || State.DISABLE_WP_CRON,
            onClick  : e => {
              ajax({
                action: 'gh_disable_internal_cron',
              }).then(r => {
                if ( r.success ){
                  State.set({
                    gh_disable_wp_cron: true,
                  })
                  morph()
                  dialog({
                    message: 'Internal cron disabled!'
                  })
                  return
                }
                dialog({
                  message: 'Something went wrong...',
                  type: 'error'
                })
              })
            },
          }, State.gh_disable_wp_cron || State.DISABLE_WP_CRON ? __('The WordPress internal cron is disabled', 'groundhogg') : __(
            'Disable the internal WordPress Cron', 'groundhogg')),
        ])),

        // Cron File
        Div({ className: 'gh-panel half' }, Div({ className: 'inside' }, [
          H3({ className: `no-margin-top ${ State.gh_cron_installed ? 'striked-completed' : '' }` },
            Span({}, sprintf(__('Install the %s Cron File', 'groundhogg'), Groundhogg.whiteLabelName))),
          Pg({}, sprintf(__(
              'We recommend installing a special file in the root directory of your site that will create a separate cron system for %s. This will be more reliable than the core WordPress cron system for handling important automation and scheduled emails.',
              'groundhogg'),
            Groundhogg.whiteLabelName)),
          State.cron_file_error ? null : Button({
            id: 'install-cron-file',
            className: 'gh-button primary',
            disabled : State.gh_cron_installed,
            onClick  : e => {
              ajax({
                action: 'gh_install_cron',
              }).then(r => {
                if (r.success) {
                  dialog({
                    message: 'Cron file installed!',
                  })
                  State.set({
                    gh_cron_installed: true,
                  })
                  morph()
                  return
                }

                dialog({
                  message: 'Cron file not installed. Please install it manually.',
                  type   : 'error',
                })

                State.set({ cron_file_error: true })
                morph()
              })
            },
          }, State.gh_cron_installed ? __('The file has been installed', 'groundhogg') : __('Install the special cron file', 'groundhogg')),
          State.cron_file_error ? Accordion({
            id      : 'cron-file-error',
            outlined: true,
            multiple: true,
            items   : [
              {
                title  : 'Install the cron file manually',
                content: () => Fragment([
                  Pg({}, __('If automatic installation of the cron file does not work follow these steps.', 'groundhogg')),
                  Ol({}, [
                    Li({}, An({ href: cron_download_url }, __('Download the cron file.', 'groundhogg'))),
                    Li({}, __('Upload it to the root directory of WordPress. This is the same folder as your <code>wp-config.php</code> file.', 'groundhogg')),
                    Li({}, __('Change the file extension from <code>.txt</code> to <code>.php</code> so that it appears as <code>gh-cron.php</code>.',
                      'groundhogg')),
                  ]),
                ]),
              },
            ],
          }) : null,
        ])),

        // Core WordPress Cron
        Div({ className: 'gh-panel half' }, Div({ className: 'inside' }, [
          H3({ className: `no-margin-top ${ wpCronOnTime() ? 'striked-completed' : '' }` }, Span({}, __('Configuring Core WordPress Cron', 'groundhogg'))),
          Pg({}, sprintf(__(
              'The core WordPress cron is responsible for %s background tasks (importing contacts, scheduling broadcasts), as well as WordPress background tasks (publishing scheduled posts, auto updating plugins).',
              'groundhogg'),
            Groundhogg.whiteLabelName)),
          !wpCronOnTime()
          ? Div({ className: 'pill attention loading-dots'}, sprintf(__('⚠️ The core WordPress cron is not running on time and was last run %s', 'groundhogg'), State.wp_last_ping_i18n ))
          : Div({ className: 'pill success'}, sprintf(__('The core WordPress cron is running on time and was last run %s.', 'groundhogg'), State.wp_last_ping_i18n )),
          Pg({}, __(
            'We recommend that the core WordPress cron be run <b>once every minute</b> for the best performance. The <i>minimum</i> recommendation is every 15 minutes.',
            'groundhogg')),
          SetupInstructions('wp-cron.php'),
        ])),

        // Groundhogg Cron
        State.gh_cron_installed ? Div({ className: 'gh-panel half' }, Div({ className: 'inside' }, [
          H3({ className: `no-margin-top ${ ghCronOnTime() ? 'striked-completed' : '' }` },
            Span({}, sprintf(__('Configuring %s Cron', 'groundhogg'), Groundhogg.whiteLabelName))),
          Pg({}, sprintf(__('The %s cron is responsible for sending scheduled emails and doing flow automation.', 'groundhogg'), Groundhogg.whiteLabelName)),
          !ghCronOnTime()
          ? Div({ className: 'pill attention loading-dots'}, [ sprintf(__('⚠️ The %s cron is not running on time and was last run %s', 'groundhogg'), Groundhogg.whiteLabelName, State.gh_last_ping_i18n ) ] )
          : Div({ className: 'pill success'}, sprintf(__('The %s cron is running on time and was last run %s.', 'groundhogg'), Groundhogg.whiteLabelName, State.gh_last_ping_i18n )),
          Pg({}, __('We recommend it be set to run <b>once every minute</b> for the best performance. The <i>minimum</i> recommendation is every 5 minutes.',
            'groundhogg')),
          SetupInstructions('gh-cron.php'),
        ])) : null,
      ])
    })
  }

  document.getElementById('cron-wrap').append(CronJobSetup())

} )()

( ($) => {

  const { tasks: TasksStore } = Groundhogg.stores
  const {
    icons,
    tinymceElement,
    addMediaToBasicTinyMCE,
    moreMenu,
    dangerConfirmationModal,
    dialog,
    escHTML,
  } = Groundhogg.element
  const {
    getOwner,
  } = Groundhogg.user
  const {
    userHasCap,
    getCurrentUser,
  } = Groundhogg.user
  const {
    formatDateTime,
  } = Groundhogg.formatting
  const {
    sprintf,
    __,
  } = wp.i18n

  const typeToIcon = {
    call   : icons.phone,
    task   : icons.tasks,
    email  : icons.email,
    meeting: icons.contact,
  }

  const taskTypes = {
    task   : __('Task', 'groundhogg'),
    call   : __('Call', 'groundhogg'),
    email  : __('Email', 'groundhogg'),
    meeting: __('Meeting', 'groundhogg'),
  }

  const isOverdue = t => t.is_overdue && !t.is_complete
  const isComplete = t => t.is_complete
  const isPending = t => !t.is_complete
  const isDueToday = t => t.is_due_today && !t.is_complete
  const isDueSoon = t => t.days_till_due < 14 && !t.is_overdue && !t.is_due_today && !t.is_complete

  const dueBy = (task) => {

    if (isOverdue(task)) {
      return `<span class="pill red" title="${ task.i18n.due_date }">${ sprintf(__('%s overdue', 'groundhogg'),
        task.i18n.due_in) }</span>`
    }

    if (isComplete(task)) {
      return `<span class="pill green" title="${ task.i18n.completed_date }">${ sprintf(__('%s ago', 'groundhogg'),
        task.i18n.completed) }</span>`
    }

    let color = ''

    if (isDueToday(task)) {
      color = 'orange'
    }
    else if (isDueSoon(task)) {
      color = 'yellow'
    }

    return `<span class="pill ${ color }" title="${ task.i18n.due_date }">${ sprintf(__('In %s', 'groundhogg'),
      task.i18n.due_in) }</span>`
  }

  const userDisplayName = user => user.ID === getCurrentUser().ID ? __('Me') : user.data.display_name

  const addedBy = (task) => {

    const {
      context,
      user_id,
    } = task.data

    let date_created = `<abbr title="${ formatDateTime(task.data.date_created) }">${ task.i18n.time_diff }</abbr>`

    let name = ''

    switch (context) {
      case 'user':
        let user = Groundhogg.filters.owners.find(o => o.ID == user_id)

        if (!user) {
          name = __('Unknown')
        }
        else {
          name = user.ID == Groundhogg.currentUser.ID ? __('me') : user.data.display_name
        }

        break
      default:
      case 'system':
        name = __('System')
        break
      case 'funnel':
        name = __('Flow')
        break
    }

    return sprintf(__('Added by %s %s ago', 'groundhogg'), name, date_created)
  }

  const openActivityForm = (type, taskId, onComplete = () => {}) => setTimeout(() => {
    AddTaskActivity({
      onSubmit: ({
        note,
        outcome,
      }) => {
        return TasksStore.patch(taskId, {
          data: {
            activity: 1,
            type,
            note,
            outcome,
          },
        }).then(onComplete)
      },
    })
  }, 100)

  const EmailCTA = (task, {
    onComplete = () => {},
  } = {}) => {

    return Fragment([
      An({
        className: 'gh-button secondary text icon',
        href     : '#',
        onClick  : async e => {
          e.preventDefault()

          const {
            object_id,
            object_type,
          } = task.data

          let to = []
          let contactIds = []

          if (object_type === 'contact') {
            let contact = await Groundhogg.stores.contacts.maybeFetchItem(object_id)
            to.push(contact.data.email)
            contactIds.push(object_id)
          }

          if (object_type === 'deal') {
            let deal = await Groundhogg.stores.deals.maybeFetchItem(object_id)
            deal.related.contacts.forEach(contact => {
              to.push(contact.data.email)
              contactIds.push(contact.ID)
            })
          }

          if (object_type === 'company') {
            let company = await Groundhogg.stores.companies.maybeFetchItem(object_id)
            if (company.data.primary_contact_id) {
              let contact = await Groundhogg.stores.contacts.maybeFetchItem(company.data.primary_contact_id)
              to.push(contact.data.email)
              contactIds.push(contact.ID)
            }
          }

          moreMenu(e.target, [
            {
              key     : 'compose',
              text    : 'Compose an email',
              onSelect: k => {
                if (to.length) {
                  Groundhogg.components.emailModal({
                    to,
                    from_email: getCurrentUser().data.user_email,
                    from_name : getCurrentUser().data.display_name,
                  }, r => {

                    const {
                      email = {},
                      log_id = '',
                      subject = '',
                    } = r

                    TasksStore.patch(task.ID, {
                      data: {
                        activity: 1,
                        type    : 'email',
                        note    : `<p>Sent a composed email <a href="#" class="view-email-log" data-log-id="${ log_id }" >${ subject }.</a></p>`,
                      },
                    }).then(onComplete)
                  })
                }
              },
            },
            {
              key     : 'template',
              text    : 'Send an email template',
              onSelect: k => {
                if (contactIds.length) {
                  Groundhogg.components.EmailTemplateModal(contactIds.length > 1 ? contactIds : contactIds[0], r => {
                    TasksStore.patch(task.ID, {
                      data: {
                        activity: 1,
                        type    : 'email',
                        note    : '',
                      },
                    }).then(onComplete)
                  })
                }
              },
            },
            {
              key     : 'activity',
              text    : 'Record email activity',
              onSelect: key => openActivityForm('email', task.ID, onComplete),
            },
          ])
        },
      }, [
        icons.email,
        ToolTip('Send a email!', 'left'),
      ]),
    ])

  }

  const CallCTA = (task, {
    onComplete = () => {},
  } = {}) => {

    return Fragment([
      An({
        className: 'gh-button secondary text icon',
        href     : '#',
        onClick  : async e => {
          e.preventDefault()
          const {
            object_id,
            object_type,
          } = task.data

          let phones = []

          const pushContactPhones = (contact) => {
            const {
              primary_phone = '',
              mobile_phone = '',
              company_phone = '',
            } = contact.meta

            const { full_name } = contact.data

            if (primary_phone) {
              phones.push([
                sprintf(__('%s\'s Primary Phone'), full_name),
                primary_phone,
              ])
            }

            if (mobile_phone) {
              phones.push([
                sprintf(__('%s\'s Mobile Phone'), full_name),
                mobile_phone,
              ])
            }

            if (company_phone) {
              phones.push([
                sprintf(__('%s\'s Business Phone'), full_name),
                company_phone,
              ])
            }
          }

          if (object_type === 'contact') {
            let contact = await Groundhogg.stores.contacts.maybeFetchItem(object_id)
            pushContactPhones(contact)
          }

          if (object_type === 'deal') {
            let deal = await Groundhogg.stores.deals.maybeFetchItem(object_id)
            deal.related.contacts.forEach(contact => pushContactPhones(contact))
          }

          if (object_type === 'company') {
            let company = await Groundhogg.stores.companies.maybeFetchItem(object_id)
            phones.push([
              __('Company Phone'),
              company.meta.phone,
            ])
          }

          moreMenu(e.target, [
            ...phones.map(([text, phone]) => ( {
              key     : phone,
              text,
              onSelect: k => {
                An({
                  href   : `tel:${ phone }`,
                  onClick: e => openActivityForm('call', task.ID, onComplete),
                }).click()
              },
            } )),
            {
              key     : 'activity',
              text    : 'Record call activity',
              onSelect: key => openActivityForm('call', task.ID, onComplete),
            },
          ])
        },
      }, [
        icons.phone,
        ToolTip('Call now!', 'left'),
      ]),
    ])
  }

  const TaskCTACallbacks = {
    call   : CallCTA,
    email  : EmailCTA,
    meeting: () => null,
    task   : () => null,
  }

  const TaskCTA = (task, ...args) => {
    // console.log( task.data.type)
    return TaskCTACallbacks[task.data.type](task, ...args)
  }

  const {
    Div,
    Form,
    Span,
    H2,
    H3,
    An,
    Img,
    Button,
    Dashicon,
    ToolTip,
    Fragment,
    Skeleton,
    InputGroup,
    Label,
    Select,
    Input,
    Textarea,
    Pg,
    Modal,
    ItemPicker,
    MiniModal,
    InputRepeater,
  } = MakeEl

  const TimeLine = (activity) => {

    if (!activity || !activity.length) {
      return null
    }

    const Activity = activity => {

      const {
        note = '',
        outcome = '',
      } = activity.meta

      const {
        activity_type = '',
      } = activity.data

      const {
        diff_time = '',
        wp_date = '',
      } = activity.i18n

      return Div({
        className: `task-activity-item ${ activity_type }`,
      }, [
        Div({
          className: 'display-flex gap-5 align-center',
        }, [
          Span({ className: 'timeline-dot' }, [
            ToolTip(diff_time, 'right'),
          ]),
          // Span({ className: 'task-activity-type' }, activity_type),
          outcome ? Span({ className: 'task-activity-outcome pill semi-dark' }, outcome) : null,
          note ? Div({ className: 'task-activity-note' }, note) : null,
        ]),
      ])
    }

    return Div({
      className: 'task-activity-timeline display-flex column gap-10 align-top',
    }, [
      ...activity.map(Activity),
    ])
  }

  const AddTaskActivity = async ({
    addButtonText = 'Add Activity',
    onSubmit = () => {},
  }) => {

    // load task outcomes
    if (!Groundhogg.stores.options.get('gh_task_outcomes')) {
      await Groundhogg.stores.options.fetch(['gh_task_outcomes'])
    }

    const State = Groundhogg.createState({
      note      : '',
      outcome   : '',
      submitting: false,
    })

    return Modal({
      width: '400px',
    }, ({
      morph,
      close,
    }) => Div({}, [

      Div({}, [
        Label({
          for: 'activity-outcome',
        }, __('What happened?')),
        `<br>`,
        Div({
          className: 'display-flex gap-5 align-center',
        }, [
          Select({
            id      : 'activity-outcome',
            name    : 'activity_outcome',
            options : [
              {
                value: '',
                text : 'Select an outcome',
              },
              ...Groundhogg.stores.options.get('gh_task_outcomes', []).map(item => ( {
                value: item,
                text : item,
              } )),
            ],
            onChange: e => {
              State.set({
                outcome: e.target.value,
              })
            },
          }),
          userHasCap('manage_options') ? An({
            href   : '#',
            id     : 'manage-task-outcomes',
            onClick: e => {

              let OutcomeState = Groundhogg.createState({
                outcomes: Groundhogg.stores.options.get('gh_task_outcomes', []),
              })

              MiniModal({
                selector       : '#manage-task-outcomes',
                closeOnFocusout: false,
              }, ({
                close,
              }) => Div({}, [
                InputRepeater({
                  id      : 'task-outcomes',
                  rows    : OutcomeState.outcomes.map(v => [v]),
                  cells   : [
                    Input,
                  ],
                  onChange: rows => {
                    OutcomeState.set({
                      outcomes: rows.map(([v]) => v),
                    })
                  },
                }),
                Button({
                  className: 'gh-button primary',
                  onClick  : e => {
                    Groundhogg.stores.options.patch({
                      gh_task_outcomes: OutcomeState.outcomes,
                    }).then(() => {
                      State.set({
                        outcomes: OutcomeState.outcomes,
                      })
                      close()
                      morph()
                    })
                  },
                }, 'Save Changes'),
              ]))
            },
          }, __('Manage outcomes')) : null,
        ]),
      ]),
      Pg({}, __('Want to add any additional context?')),
      Textarea({
        id       : 'activity-note',
        name     : 'activity_note',
        className: 'full-width',
        value    : State.note,
        onCreate : el => {
          try {
            wp.editor.remove('activity-note')
          }
          catch (err) {}

          setTimeout(() => {
            addMediaToBasicTinyMCE()
            tinymceElement('activity-note', {
              quicktags    : false,
              taskTemplates: true,
            }, content => {
              State.set({
                note: content,
              })
            })
          }, 10)
        },
      }),
      Div({
        className: 'display-flex gap-5 flex-end space-above-10',
      }, [
        Button({
          className: 'gh-button danger text',
          disabled : State.submitting,
          onClick  : e => close(),
        }, __('Cancel')),
        Button({
          className: 'gh-button primary',
          id       : 'add-activity',
          disabled : State.submitting,
          onClick  : e => {

            State.set({
              submitting: true,
            })

            morph()

            onSubmit({
              note   : State.note,
              outcome: State.outcome,
            }).then(close).catch(err => close)

          },
        }, addButtonText),
      ]),
    ]))

  }

  const BetterObjectTasks = ({
    object_type = '',
    object_id = 0,
    title = __('Tasks', 'groundhogg'),
    ...props
  } = {}) => {

    const State = Groundhogg.createState({
      adding      : false,
      editing     : false,
      bulk_edit   : false,
      filter      : isPending,
      tasks       : [],
      selected    : [],
      loaded      : false,
      edit_summary: '',
      edit_date   : '',
      edit_time   : '',
      edit_content: '',
      assigned_to : 0,
      edit_type   : 'task',
      myTasks     : !( object_id && object_type ),
    })

    const clearEditState = () => State.set({
      edit_summary: '',
      edit_date   : '',
      edit_time   : '',
      edit_content: '',
      assigned_to : 0,
      edit_type   : 'task',
      editing     : false,
    })

    const fetchTasks = () => {

      let query = {
        limit  : 99,
        orderby: 'due_date',
        order  : 'ASC',
      }

      // tasks for anything, but only assigned to the current user
      if (!object_type && !object_id) {
        query = {
          user_id   : getCurrentUser().ID,
          incomplete: true,
          ...query,
        }
      }
      else {
        query = {
          object_id,
          object_type,
          ...query,
        }
      }

      return TasksStore.fetchItems(query).then(tasks => {

        State.set({
          loaded: true,
          tasks : tasks.map(({ ID }) => ID),
        })

        return tasks

      })
    }

    return Div({
      ...props,

      id       : 'my-tasks',
      className: 'tasks-widget',
    }, morph => {

      if (!State.loaded) {

        fetchTasks().then(morph)

        return Skeleton({
          style: {
            padding: '10px',
          },
        }, [
          'full',
          'full',
          'full',
        ])

      }

      /**
       * The form for adding/editing the task details
       *
       * @returns {*}
       * @constructor
       */
      const TaskDetails = () => {

        return Form({
          className: 'task display-grid gap-10',
          onSubmit : e => {
            e.preventDefault()

            if (State.adding) {

              TasksStore.post({
                data: {
                  object_id,
                  object_type,
                  summary : State.edit_summary,
                  content : State.edit_content,
                  user_id : State.assigned_to,
                  type    : State.edit_type,
                  due_date: `${ State.edit_date } ${ State.edit_time }`,
                },
              }).then(task => {

                State.set({
                  adding: false,
                  tasks : [
                    ...State.tasks,
                    task.ID,
                  ], // add the new task ID
                })

                clearEditState()

                morph()
              })

            }
            else {

              TasksStore.patch(State.editing, {
                data: {
                  summary : State.edit_summary,
                  content : State.edit_content,
                  type    : State.edit_type,
                  user_id : State.assigned_to,
                  due_date: `${ State.edit_date } ${ State.edit_time }`,
                },
              }).then(() => {

                clearEditState()

                morph()

              })
            }

          },
        }, [
          Div({
            className: 'full display-flex gap-10',
          }, [
            Div({
              className: 'full-width',
            }, [
              Label({
                for: 'task-summary',
              }, __('Task summary')),
              Input({
                className: 'full-width',
                id       : 'task-summary',
                name     : 'summary',
                required : true,
                value    : State.edit_summary,
                onChange : e => State.set({
                  edit_summary: e.target.value,
                }),
              }),
            ]),

            Div({
              className: '',
            }, [
              Label({
                for: 'task-type',
              }, __('Type')),
              `<br>`,
              Select({
                id      : 'task-type',
                options : taskTypes,
                selected: State.edit_type,
                onChange: e => State.set({
                  edit_type: e.target.value,
                }),
              }),
            ]),
          ]),
          Div({
            className: 'half',
          }, [
            Label({
              for: 'task-date',
            }, __('Due Date')),
            InputGroup([
              Input({
                type     : 'date',
                id       : 'task-date',
                className: 'full-width',
                value    : State.edit_date,
                onChange : e => State.set({
                  edit_date: e.target.value,
                }),
              }),
              Input({
                type     : 'time',
                id       : 'task-time',
                name     : 'time',
                className: 'full-width',
                value    : State.edit_time,
                onChange : e => State.set({
                  edit_time: e.target.value,
                }),
              }),
            ]),
          ]),
          Div({
            className: 'half',
          }, [
            Label({
              for: 'task-assigned-to',
            }, __('Assigned To')),
            `<br>`,
            ItemPicker({
              id          : `task-assigned-to`,
              noneSelected: __('Select a user...', 'groundhogg'),
              selected    : State.assigned_to ? {
                id  : State.assigned_to,
                text: userDisplayName(getOwner(State.assigned_to)),
              } : [],
              multiple    : false,
              style       : {
                flexGrow: 1,
              },
              fetchOptions: (search) => {
                search = new RegExp(search, 'i')
                let options = Groundhogg.filters.owners.map(u => ( {
                  id  : u.ID,
                  text: userDisplayName(u),
                } )).filter(({ text }) => text.match(search))
                return Promise.resolve(options)
              },
              onChange    : item => {
                State.set({
                  assigned_to: item.id,
                })
              },
            }),
          ]),
          Div({
            className: 'full',
          }, [
            Label({
              for: 'edit-task-content',
            }, __('Details')),
            Textarea({
              id       : 'edit-task-content',
              className: 'full-width',
              value    : State.edit_content,
              onCreate : el => {
                try {
                  wp.editor.remove('edit-task-content')
                }
                catch (err) {

                }

                setTimeout(() => {
                  addMediaToBasicTinyMCE()
                  tinymceElement('edit-task-content', {
                    quicktags    : false,
                    taskTemplates: true,
                    replacements : true,
                  }, content => {
                    State.set({
                      edit_content: content,
                    })
                  })
                }, 10)
              },
            }),
          ]),
          Div({
            className: 'full display-flex flex-end gap-5',
          }, [
            Button({
              className: 'gh-button danger text',
              id       : 'cancel-task-changes',
              type     : 'button',
              onClick  : e => {
                clearEditState()
                State.set({
                  adding : false,
                  editing: false,
                })

                morph()
              },
            }, 'Cancel'),
            Button({
              className: 'gh-button primary',
              id       : 'update-task',
              type     : 'submit',
            }, State.adding ? 'Create Task' : 'Update Task'),
          ]),
        ])
      }

      /**
       * The task itself
       *
       * @param task
       * @returns {*}
       * @constructor
       */
      const Task = task => {

        const {
          content,
          type,
          due_date,
          context,
          user_id,
          summary,
        } = task.data

        const TaskState = Groundhogg.createState({
          showTimeline: false,
        })

        /**
         * If the task belongs to the current user
         *
         * @returns {boolean}
         */
        const belongsToMe = () => user_id == Groundhogg.currentUser.ID

        let assocIcon = null

        if (task.associated.img) {
          assocIcon = Img({
            src: task.associated.img,
          })
        }
        else if (task.associated.icon) {
          assocIcon = Dashicon(task.associated.icon)
        }

        return Div({
          className: `task ${ task.is_complete ? 'complete' : 'pending' } ${ task.is_overdue ? 'overdue' : '' }`,
          id       : `task-item-${ task.ID }`,
          dataId   : task.ID,
        }, taskMorph => Fragment([

          Div({
            className: 'task-header',
          }, [
            typeToIcon[type],
            Input({
              type     : 'checkbox',
              name     : 'tasks[]',
              className: 'select-task',
              checked  : State.selected.includes(task.ID),
              onChange : e => {
                if (e.target.checked) {
                  State.selected.push(task.ID)
                }
                else {
                  State.set({
                    selected: State.selected.filter(id => id !== task.ID),
                  })
                }
                morph()
              },
            }),
            summary ? Span({ className: 'summary' }, escHTML(summary)) : null,
            Div({
              className: 'display-flex',
              style    : {
                marginLeft: 'auto',
              },
            }, [
              TaskCTA(task, {
                onComplete: morph,
              }),
              task.is_complete ? null : Button({
                id       : `task-mark-complete-${ task.ID }`,
                className: 'gh-button text icon primary mark-complete',
                onClick  : e => {
                  AddTaskActivity({
                    addButtonText: __('Mark complete', 'groundhogg'),
                    onSubmit     : ({
                      note,
                      outcome,
                    }) => {
                      document.getElementById(`task-item-${ task.ID }`).classList.add('completing')
                      return TasksStore.patch(task.ID, {
                        data: {
                          complete: 1,
                          note,
                          outcome,
                        },
                      }).then(task => {
                        dialog({
                          message: __('Task completed!'),
                        })

                        morph()
                      })
                    },
                  })
                },
              }, [
                Dashicon('thumbs-up'),
                ToolTip(__('Mark complete', 'groundhogg'), 'left'),
              ]),
              Button({
                id       : `task-actions-${ task.ID }`,
                className: 'gh-button text icon secondary task-more',
                onClick  : e => {

                  let items = [
                    {
                      key     : 'edit',
                      cap     : belongsToMe() ? 'edit_tasks' : 'edit_others_tasks',
                      text    : __('Edit'),
                      onSelect: () => {
                        State.set({
                          editing     : task.ID,
                          edit_summary: summary,
                          edit_date   : due_date.split(' ')[0],
                          edit_time   : due_date.split(' ')[1],
                          edit_content: content,
                          edit_type   : type,
                          assigned_to : user_id,
                        })
                        morph()
                      },
                    },
                    {
                      key     : 'record-activity',
                      cap     : 'edit_tasks',
                      text    : __('Record activity'),
                      onSelect: () => {
                        AddTaskActivity({
                          onSubmit: ({
                            note,
                            outcome,
                          }) => {
                            return TasksStore.patch(task.ID, {
                              data: {
                                activity: 1,
                                type    : task.data.type,
                                note,
                                outcome,
                              },
                            }).then(task => {
                              dialog({
                                message: __('Activity recorded!'),
                              })

                              morph()
                            })
                          },
                        })
                      },
                    },
                    {
                      key     : 'delete',
                      cap     : belongsToMe() ? 'delete_tasks' : 'delete_others_tasks',
                      text    : `<span class="gh-text danger">${ __('Delete') }</span>`,
                      onSelect: () => {
                        dangerConfirmationModal({
                          alert    : `<p>${ __('Are you sure you want to delete this task?', 'groundhogg') }</p>`,
                          onConfirm: () => {
                            TasksStore.delete(task.ID).then(() => {
                              // also remove from state
                              State.tasks.splice(State.tasks.indexOf(task.ID), 1)
                              morph()
                            })
                          },
                        })
                      },
                    },
                  ]

                  if (isDueSoon(task) || isDueToday(task) || isOverdue(task)) {
                    items.unshift({
                      key     : 'incomplete',
                      cap     : belongsToMe() ? 'edit_tasks' : 'edit_others_tasks',
                      text    : __('Snooze'),
                      onSelect: () => {
                        TasksStore.patch(task.ID, {
                          data: { snooze: 1 },
                        }).then(() => {
                          morph()
                        })
                      },
                    })
                  }

                  if (task.is_complete) {
                    items.unshift({
                      key     : 'incomplete',
                      cap     : belongsToMe() ? 'edit_tasks' : 'edit_others_tasks',
                      text    : __('Mark incomplete'),
                      onSelect: () => {
                        TasksStore.incomplete(task.ID).then(() => {
                          morph()
                        })
                      },
                    })
                  }

                  moreMenu(e.currentTarget, items.filter(i => userHasCap(i.cap)))

                },
              }, icons.verticalDots),
            ]),
          ]),
          !object_id ? An({
            className: 'associated-object',
            href     : task.associated.link,
            style    : {
              width: 'fit-content',
            },
          }, [
            assocIcon,
            task.associated.name,
          ]) : null,
          Div({
              className: 'display-flex gap-5 align-center details flex-wrap',
            }, [
              dueBy(task),
              Span({ className: 'added-by' }, addedBy(task)),
            ],
          ),
          Div({
            className: 'task-content space-above-10',
          }, content),
          task.activity.length ? An({
            href     : '#',
            className: 'toggle-activity',
            onClick  : e => {
              e.preventDefault()
              TaskState.set({
                showTimeline: !TaskState.showTimeline,
              })
              taskMorph()
            },
          }, TaskState.showTimeline ? 'Hide timeline &uarr;' : 'Show timeline &darr;') : null,
          TaskState.showTimeline ? TimeLine(task.activity) : null,
        ]))
      }

      let tasks = State.tasks.map(id => TasksStore.get(id))

      tasks = tasks.sort((a, b) => a.due_timestamp - b.due_timestamp)

      let filteredTasks = tasks.filter(State.filter)

      /**
       * Update the current filter on the tasks
       *
       * @param filter
       */
      const setFilter = filter => {
        State.set({
          filter,
          adding : false,
          editing: false,
        })
        morph()
      }

      const FilterPill = ({
        id = '',
        text = '',
        color = '',
        filter = isPending,
      }) => {

        let num = tasks.filter(filter).length

        if (!num) {
          return null
        }

        return Span({
          id       : `filter-${ id || color }`,
          className: `pill ${ color } clickable ${ State.filter === filter ? 'bold' : '' }`,
          onClick  : e => setFilter(filter),
        }, sprintf(text, num))
      }

      return Fragment([
        title ? H3({}, title) : null,

        object_id || tasks.length ? Div({
          className: 'tasks-header',
        }, [
          FilterPill({
            id    : 'overdue',
            text  : __('%d overdue', 'groundhogg'),
            color : 'red',
            filter: isOverdue,
          }),

          FilterPill({
            id    : 'due-today',
            text  : __('%d due today', 'groundhogg'),
            color : 'orange',
            filter: isDueToday,
          }),

          FilterPill({
            id    : 'due-soon',
            text  : __('%d due soon', 'groundhogg'),
            color : 'yellow',
            filter: isDueSoon,
          }),

          FilterPill({
            id    : 'pending',
            text  : __('%d pending', 'groundhogg'),
            filter: isPending,
          }),

          FilterPill({
            id    : 'complete',
            text  : __('%d complete', 'groundhogg'),
            color : 'green',
            filter: isComplete,
          }),

          userHasCap('add_tasks') && object_id ? Button({
            id       : 'add-tasks',
            className: 'gh-button secondary text icon',
            onClick  : e => {

              if (State.editing) {
                clearEditState()
              }

              State.set({
                adding     : true,
                assigned_to: getCurrentUser().ID,
              })

              morph()
            },
          }, [
            Dashicon('plus-alt2'),
            ToolTip('Add Task', 'left'),
          ]) : null,
        ]) : null,
        State.selected.length ? Div({
          className: 'display-flex gap-5',
          style    : {
            padding: '0 0 10px 10px',
          },
        }, [
          // Edit
          !userHasCap('edit_tasks') ? null : Button({
            className: `gh-button ${ State.bulk_edit ? 'primary' : 'secondary' } small`,
            onClick  : e => {
              State.set({
                bulk_edit: !State.bulk_edit,
              })

              if (State.bulk_edit) {
                clearEditState()
                State.set({
                  edit_type: '', // no default type
                })
              }
              morph()
            },
          }, __('Edit')),
          // Snooze
          !userHasCap('edit_tasks') ? null : Button({
            className: 'gh-button secondary small',
            disabled : State.bulk_edit,
            onClick  : e => {

              TasksStore.patchMany({
                query: {
                  include: State.selected,
                },
                data : { snooze: 1 },
              }).then(() => {
                dialog({
                  message: sprintf('%d tasks snoozed!', State.selected.length),
                })
                morph()
              })

            },
          }, __('Snooze')),
          // Delete
          !userHasCap('delete_tasks') ? null : Button({
            className: 'gh-button danger small',
            disabled : State.bulk_edit,
            onClick  : e => {
              dangerConfirmationModal({
                alert    : `<p>${ sprintf(__('Are you sure you want to delete these %d tasks?', 'groundhogg'), State.selected.length) }</p>`,
                onConfirm: () => {
                  TasksStore.deleteMany({
                    include: State.selected,
                  }).then(() => {
                    dialog({
                      message: sprintf('%d tasks deleted!', State.selected.length),
                    })
                    // also remove from state
                    State.set({
                      tasks   : State.tasks.filter(task => !State.selected.includes(task)),
                      selected: [],
                    })
                    morph()
                  })
                },
              })
            },
          }, __('Delete')),
          // Mark complete
          !userHasCap('edit_tasks') ? null : Button({
            className: 'gh-button primary small',
            disabled : State.bulk_edit,
            onClick  : e => {

              AddTaskActivity({
                addButtonText: __('Mark complete', 'groundhogg'),
                onSubmit     : ({
                  note,
                  outcome,
                }) => {

                  State.selected.forEach(id => {
                    document.getElementById(`task-item-${ id }`).classList.add('completing')
                  })

                  return TasksStore.patchMany({
                    query: {
                      include: State.selected,
                    },
                    data : {
                      complete: 1,
                      note,
                      outcome,
                    },
                  }).then(() => {
                    dialog({
                      message: sprintf('%d tasks completed!', State.selected.length),
                    })
                    morph()
                  })
                },
              })
            },
          }, __('Mark Complete')),
          // Deselect all
          Button({
            className: 'gh-button secondary text small',
            onClick  : e => {
              State.set({
                bulk_edit: false,
                selected : [],
              })
              morph()
            },
          }, __('Clear Selection')),
        ]) : null,
        // Bulk Edit
        State.bulk_edit ? Div({
          className: 'display-flex gap-10 align-bottom flex-wrap',
          style    : {
            padding: '0 10px 10px 10px',
          },
        }, [

          Div({}, [
            Label({
              for: 'task-type',
            }, __('Type')),
            `<br>`,
            Select({
              id      : 'task-type',
              options : {
                '': 'No change',
                ...taskTypes,
              },
              selected: State.edit_type,
              onChange: e => State.set({
                edit_type: e.target.value,
              }),
            }),
          ]),

          Div({}, [
            Label({
              for: 'task-date',
            }, __('Due Date')),
            InputGroup([
              Input({
                type     : 'date',
                id       : 'task-date',
                className: 'full-width',
                value    : State.edit_date,
                onChange : e => State.set({
                  edit_date: e.target.value,
                }),
              }),
              Input({
                type     : 'time',
                id       : 'task-time',
                name     : 'time',
                className: 'full-width',
                value    : State.edit_time,
                onChange : e => State.set({
                  edit_time: e.target.value,
                }),
              }),
            ]),
          ]),

          Div({}, [
            Label({
              for: 'task-assigned-to',
            }, __('Assigned To')),
            `<br>`,
            ItemPicker({
              id          : `task-assigned-to`,
              noneSelected: __('Assign to a new user...', 'groundhogg'),
              selected    : State.assigned_to ? {
                id  : State.assigned_to,
                text: userDisplayName(getOwner(State.assigned_to)),
              } : [],
              multiple    : false,
              style       : {
                flexGrow: 1,
              },
              fetchOptions: (search) => {
                search = new RegExp(search, 'i')
                let options = Groundhogg.filters.owners.map(u => ( {
                  id  : u.ID,
                  text: userDisplayName(u),
                } )).filter(({ text }) => text.match(search))
                return Promise.resolve(options)
              },
              onChange    : item => {
                State.set({
                  assigned_to: item.id,
                })
              },
            }),
          ]),
          Button({
            className: 'gh-button primary',
            onClick  : e => {

              let data = {}

              if (State.edit_date && State.edit_time) {
                data.due_date = `${ State.edit_date } ${ State.edit_time }`
              }

              if (State.assigned_to) {
                data.user_id = State.assigned_to
              }

              if (State.edit_type) {
                data.type = State.edit_type
              }

              TasksStore.patchMany({
                query: {
                  include: State.selected,
                },
                data,
              }).then(() => {
                clearEditState()
                State.set({
                  bulk_edit: false,
                })
                dialog({
                  message: sprintf('%d tasks updated!', State.selected.length),
                })
                morph()
              })
            },
          }, __('Update')),
        ]) : null,
        // Add Task Form
        State.adding ? TaskDetails() : null,
        ...filteredTasks.map(task => State.editing == task.ID ? TaskDetails(task) : Task(task)),
        tasks.length || State.adding ? null : Pg({
          style: {
            textAlign: 'center',
          },
        }, __('🎉 No pending tasks!', 'groundhogg')),
      ])
    })

  }

  const ObjectTasks = (selector, {
    object_type = '',
    object_id = 0,
    title = __('Tasks', 'groundhogg'),
  }) => {

    document.querySelector(selector).append(BetterObjectTasks({
      object_type,
      object_id,
      title,
    }))
  }

  const MyTasks = (selector, props) => {
    document.querySelector(selector).append(BetterObjectTasks({
      title: false,
    }))
  }

  Groundhogg.taskEditor = ObjectTasks
  Groundhogg.ObjectTasks = BetterObjectTasks
  Groundhogg.myTasks = MyTasks

} )(jQuery)

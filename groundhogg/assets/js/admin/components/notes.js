( ($) => {

  const { notes: NotesStore } = Groundhogg.stores
  const {
    icons,
    tinymceElement,
    addMediaToBasicTinyMCE,
    moreMenu,
    dangerConfirmationModal,
    dialog,
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
    note   : icons.note,
    task   : icons.tasks,
    call   : icons.phone,
    email  : icons.email,
    meeting: icons.contact,
  }

  const noteTypes = {
    note   : __('Note', 'groundhogg'),
    call   : __('Call', 'groundhogg'),
    email  : __('Email', 'groundhogg'),
    meeting: __('Meeting', 'groundhogg'),
  }

  const addedBy = (note) => {

    const {
      context,
      user_id,
    } = note.data

    let date_created = `<abbr title="${ formatDateTime(note.data.date_created) }">${ note.i18n.time_diff }</abbr>`

    let name

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

  const {
    Div,
    Form,
    Span,
    H3,
    Button,
    Dashicon,
    ToolTip,
    Fragment,
    Skeleton,
    Label,
    Select,
    Input,
    Textarea,
    Pg,
  } = MakeEl

  const BetterObjectNotes = ({
    object_type = '',
    object_id = 0,
    title = __('Notes', 'groundhogg'),
    ...props
  } = {}) => {

    const State = Groundhogg.createState({
      adding      : false,
      editing     : false,
      bulk_edit   : false,
      notes       : [],
      selected    : [],
      loaded      : false,
      edit_content: '',
      edit_type   : 'note',
      myNotes     : !( object_id && object_type ),
    })

    const clearEditState = () => State.set({
      edit_summary: '',
      edit_content: '',
      edit_type   : 'note',
      editing     : false,
      adding      : false,
    })

    const fetchNotes = () => {

      let query = {
        limit  : 99,
        orderby: 'date_created',
        order  : 'DESC',
      }

      // notes for anything, but only assigned to the current user
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

      return NotesStore.fetchItems(query).then(notes => {

        State.set({
          loaded: true,
          notes : notes.map(({ ID }) => ID),
        })

        return notes

      })
    }

    return Div({
      ...props,

      id       : 'my-notes',
      className: 'notes-widget',
    }, morph => {

      if (!State.loaded) {

        fetchNotes().then(morph)

        return Skeleton({
          style: {
            padding: '10px'
          }
        }, [
          'full',
          'full',
          'full',
        ])

      }

      /**
       * The form for adding/editing the note details
       *
       * @returns {*}
       * @constructor
       */
      const NoteDetails = () => {

        return Form({
          className: 'note display-grid gap-10',
          onSubmit : e => {
            e.preventDefault()

            if (State.adding) {

              NotesStore.post({
                force: 1,
                data: {
                  object_id,
                  object_type,
                  content: State.edit_content,
                  user_id: getCurrentUser().ID,
                  type   : State.edit_type,
                },
              }).then(note => {

                State.set({
                  adding: false,
                  notes : [
                    note.ID,
                    ...State.notes,
                  ], // add the new note ID
                })

                clearEditState()

                morph()
              })

            }
            else {

              NotesStore.patch(State.editing, {
                data: {
                  content: State.edit_content,
                  type   : State.edit_type,
                },
              }).then(() => {

                clearEditState()

                morph()

              })
            }

          },
        }, [
          Div({
            className: 'full',
          }, [
            Label({
              for: 'edit-note-content',
            }, __('Details')),
            Textarea({
              id       : 'edit-note-content',
              className: 'full-width',
              value    : State.edit_content,
              onCreate : el => {
                try {
                  wp.editor.remove('edit-note-content')
                }
                catch (err) {

                }

                setTimeout(() => {
                  addMediaToBasicTinyMCE()
                  tinymceElement('edit-note-content', {
                    quicktags    : false,
                    noteTemplates: true,
                    replacements: true,
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
            className: 'full display-flex flex-end gap-5 align-bottom',
          }, [
            Div({
              className: '',
            }, [
              Label({
                for: 'note-type',
              }, __('Type')),
              `<br>`,
              Select({
                id      : 'note-type',
                options : noteTypes,
                selected: State.edit_type,
                onChange: e => State.set({
                  edit_type: e.target.value,
                }),
              }),
            ]),
            Button({
              className: 'gh-button danger text',
              id       : 'cancel-note-changes',
              type     : 'button',
              style    : {
                marginLeft: 'auto',
              },
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
              id       : 'update-note',
              type     : 'submit',
            }, State.adding ? 'Create Note' : 'Update Note'),
          ]),
        ])
      }

      /**
       * The note itself
       *
       * @param note
       * @returns {*}
       * @constructor
       */
      const Note = note => {

        const {
          content,
          type,
          user_id,
        } = note.data

        /**
         * If the note belongs to the current user
         *
         * @returns {boolean}
         */
        const belongsToMe = () => user_id == Groundhogg.currentUser.ID

        return Div({
          className: `note ${type}`,
          id       : `note-item-${ note.ID }`,
          dataId   : note.ID,
        }, noteMorph => Fragment([

          Div({
            className: 'note-header',
          }, [
            typeToIcon[type],
            Input({
              type     : 'checkbox',
              name     : 'notes[]',
              className: 'select-note',
              checked  : State.selected.includes(note.ID),
              onChange : e => {
                if (e.target.checked) {
                  State.selected.push(note.ID)
                }
                else {
                  State.set({
                    selected: State.selected.filter(id => id !== note.ID),
                  })
                }
                morph()
              },
            }),
            Span({ className: 'added-by' }, addedBy(note)),
            Div({
              className: 'display-flex',
              style    : {
                marginLeft: 'auto',
              },
            }, [
              Button({
                id       : `note-actions-${ note.ID }`,
                className: 'gh-button text icon secondary note-more',
                onClick  : e => {

                  let items = [
                    {
                      key     : 'edit',
                      cap     : belongsToMe() ? 'edit_notes' : 'edit_others_notes',
                      text    : __('Edit'),
                      onSelect: () => {
                        clearEditState()
                        State.set({
                          editing     : note.ID,
                          edit_content: content,
                          edit_type   : type,
                        })
                        morph()
                      },
                    },
                    {
                      key     : 'delete',
                      cap     : belongsToMe() ? 'delete_notes' : 'delete_others_notes',
                      text    : `<span class="gh-text danger">${ __('Delete') }</span>`,
                      onSelect: () => {
                        dangerConfirmationModal({
                          alert    : `<p>${ __('Are you sure you want to delete this note?', 'groundhogg') }</p>`,
                          onConfirm: () => {
                            NotesStore.delete(note.ID).then(() => {
                              // also remove from state
                              State.notes.splice(State.notes.indexOf(note.ID), 1)
                              morph()
                            })
                          },
                        })
                      },
                    },
                  ]

                  moreMenu(e.currentTarget, items.filter(i => userHasCap(i.cap)))

                },
              }, icons.verticalDots),
            ]),
          ]),
          Div({
            className: 'note-content space-above-10',
          }, content),
        ]))
      }

      let notes = State.notes.map(id => NotesStore.get(id))

      return Fragment([
        title ? H3({}, title) : null,

        object_id || notes.length ? Div({
          className: 'notes-header',
        }, [
          userHasCap('add_notes') && object_id ? Button({
            id       : 'add-notes',
            className: 'gh-button secondary text icon',
            onClick  : e => {

              if (State.editing) {
                clearEditState()
              }

              State.set({
                adding: true,
              })

              morph()
            },
          }, [
            Dashicon('plus-alt2'),
            ToolTip('Add Note', 'left'),
          ]) : null,
        ]) : null,
        State.selected.length ? Div({
          className: 'display-flex gap-5',
          style    : {
            padding: '0 0 10px 10px',
          },
        }, [
          // Edit
          !userHasCap('edit_notes') ? null : Button({
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
          // Delete
          !userHasCap('delete_notes') ? null : Button({
            className: 'gh-button danger small',
            disabled : State.bulk_edit,
            onClick  : e => {
              dangerConfirmationModal({
                alert    : `<p>${ sprintf(__('Are you sure you want to delete these %d notes?', 'groundhogg'), State.selected.length) }</p>`,
                onConfirm: () => {
                  NotesStore.deleteMany({
                    include: State.selected,
                  }).then(() => {
                    dialog({
                      message: sprintf('%d notes deleted!', State.selected.length),
                    })
                    // also remove from state
                    State.set({
                      notes   : State.notes.filter(note => !State.selected.includes(note)),
                      selected: [],
                    })
                    morph()
                  })
                },
              })
            },
          }, __('Delete')),
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
              for: 'note-type',
            }, __('Type')),
            `<br>`,
            Select({
              id      : 'note-type',
              options : {
                '': 'No change',
                ...noteTypes,
              },
              selected: State.edit_type,
              onChange: e => State.set({
                edit_type: e.target.value,
              }),
            }),
          ]),
          Button({
            className: 'gh-button primary',
            onClick  : e => {

              let data = {}

              if (State.edit_type) {
                data.type = State.edit_type
              }

              NotesStore.patchMany({
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
                  message: sprintf('%d notes updated!', State.selected.length),
                })
                morph()
              })
            },
          }, __('Update')),
        ]) : null,
        // Add Note Form
        State.adding ? NoteDetails() : null,
        ...notes.map(note => State.editing == note.ID ? NoteDetails(note) : Note(note)),
        notes.length || State.adding ? null : Pg({
          style: {
            textAlign: 'center',
          },
        }, __('No notes yet.', 'groundhogg')),
      ])
    })
  }

  Groundhogg.noteEditor = (selector, props = {}) => {
    let el = document.querySelector(selector)
    el.append(BetterObjectNotes(props))
  }

  Groundhogg.ObjectNotes = BetterObjectNotes

} )(jQuery)

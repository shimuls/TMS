import * as types from './mutation-types'

export default {
  [types.SET_BOOKINGS] (state, bookings) {
    state.bookings = bookings
  },

  [types.SET_TOTAL_BOOKINGS] (state, totalBookings) {
    state.totalBookings = totalBookings
  },

  [types.ADD_BOOKING] (state, data) {
    state.bookings.push(data)
  },

  [types.DELETE_BOOKING] (state, id) {
    let index = state.bookings.findIndex(booking => booking.id === id)
    state.bookings.splice(index, 1)
  },

  [types.SET_SELECTED_BOOKINGS] (state, data) {
    state.selectedBookings = data
  },

  [types.UPDATE_BOOKING] (state, data) {
    let pos = state.bookings.findIndex(booking => booking.id === data.booking.id)

    state.bookings[pos] = data.booking
  },

  [types.UPDATE_BOOKING_STATUS] (state, data) {
    let pos = state.bookings.findIndex(booking => booking.id === data.id)

    if (state.bookings[pos]) {
      state.bookings[pos].status = data.status
    }
  },

  [types.RESET_SELECTED_BOOKINGS] (state, data) {
    state.selectedBookings = []
    state.selectAllField = false
  },

  [types.DELETE_MULTIPLE_BOOKINGS] (state, selectedBookings) {
    selectedBookings.forEach((booking) => {
      let index = state.bookings.findIndex(_inv => _inv.id === booking.id)
      state.bookings.splice(index, 1)
    })
    state.selectedBookings = []
  },

  [types.SET_TEMPLATE_ID] (state, templateId) {
    state.bookingTemplateId = templateId
  },

  [types.SELECT_CUSTOMER] (state, data) {
    state.selectedCustomer = data
  },

  [types.RESET_SELECTED_CUSTOMER] (state, data) {
    state.selectedCustomer = null
  },

  [types.SET_SELECT_ALL_STATE] (state, data) {
    state.selectAllField = data
  }
}

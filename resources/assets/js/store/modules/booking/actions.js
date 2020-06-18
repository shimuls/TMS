import * as types from './mutation-types'
import * as dashboardTypes from '../dashboard/mutation-types'

export const fetchBookings = ({ commit, dispatch, state }, params) => {
  return new Promise((resolve, reject) => {
    window.axios.get(`/api/bookings`, {params}).then((response) => {
      commit(types.SET_BOOKINGS, response.data.bookings.data)
      commit(types.SET_TOTAL_BOOKINGS, response.data.bookingTotalCount)
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}

export const fetchCreateBooking = ({ commit, dispatch, state }) => {
  return new Promise((resolve, reject) => {
    window.axios.get(`/api/bookings/create`).then((response) => {
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}

export const fetchBooking = ({ commit, dispatch, state }, id) => {
  return new Promise((resolve, reject) => {
    window.axios.get(`/api/bookings/${id}/edit`).then((response) => {
      //alert(id)
      //commit(types.SET_TEMPLATE_ID, response.data.booking.booking_template_id)
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}

export const fetchViewBooking = ({ commit, dispatch, state }, id) => {
  return new Promise((resolve, reject) => {
    window.axios.get(`/api/bookings/${id}`).then((response) => {
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}

/* export const sendEmail = ({ commit, dispatch, state }, data) => {
  return new Promise((resolve, reject) => {
    window.axios.post(`/api/bookings/send`, data).then((response) => {
      if (response.data.success) {
        commit(types.UPDATE_BOOKING_STATUS, {id: data.id, status: 'SENT'})
        commit('dashboard/' + dashboardTypes.UPDATE_BOOKING_STATUS, {id: data.id, status: 'SENT'}, { root: true })
      }
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}
 */
// export const SentEmail = ({ commit, dispatch, state }, bookingId) => {
//   return new Promise((resolve, reject) => {
//     window.axios.post(`/api/bookings/sent/${bookingId}`).then((response) => {
//       resolve(response)
//     }).catch((err) => {
//       reject(err)
//     })
//   })
// }

export const addBooking = ({ commit, dispatch, state }, data) => {
  return new Promise((resolve, reject) => {
    window.axios.post('/api/bookings', data).then((response) => {
      commit(types.ADD_BOOKING, response.data)
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}

/* export const deleteBooking = ({ commit, dispatch, state }, id) => {
  return new Promise((resolve, reject) => {
    window.axios.delete(`/api/bookings/${id}`).then((response) => {
      if (response.data.error) {
        resolve(response)
      } else {
        commit(types.DELETE_BOOKING, id)
        commit('dashboard/' + dashboardTypes.DELETE_BOOKING, id, { root: true })
        resolve(response)
      }
    }).catch((err) => {
      reject(err)
    })
  })
}
 */
export const deleteMultipleBookings = ({ commit, dispatch, state }, id) => {
  return new Promise((resolve, reject) => {
    window.axios.post(`/api/bookings/delete`, {'id': state.selectedBookings}).then((response) => {
      if (response.data.error) {
        resolve(response)
      } else {
        commit(types.DELETE_MULTIPLE_BOOKINGS, state.selectedBookings)
        resolve(response)
      }
    }).catch((err) => {
      reject(err)
    })
  })
}

export const updateBooking = ({ commit, dispatch, state }, data) => {
  return new Promise((resolve, reject) => {
    window.axios.put(`/api/bookings/${data.id}`, data).then((response) => {
      if (response.data.booking) {
        commit(types.UPDATE_BOOKING, response.data)
      }
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}

/* export const markAsSent = ({ commit, dispatch, state }, data) => {
  return new Promise((resolve, reject) => {
    window.axios.post(`/api/bookings/mark-as-sent`, data).then((response) => {
      commit(types.UPDATE_BOOKING_STATUS, {id: data.id, status: 'SENT'})
      commit('dashboard/' + dashboardTypes.UPDATE_BOOKING_STATUS, {id: data.id, status: 'SENT'}, { root: true })
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
} */

export const cloneBooking = ({ commit, dispatch, state }, data) => {
  return new Promise((resolve, reject) => {
    window.axios.post(`/api/bookings/clone`, data).then((response) => {
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}

export const searchBooking = ({ commit, dispatch, state }, data) => {
  return new Promise((resolve, reject) => {
    window.axios.get(`/api/bookings?${data}`).then((response) => {
      // commit(types.UPDATE_BOOKING, response.data)
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}

export const selectBooking = ({ commit, dispatch, state }, data) => {
  commit(types.SET_SELECTED_BOOKINGS, data)
  if (state.selectedBookings.length === state.bookings.length) {
    commit(types.SET_SELECT_ALL_STATE, true)
  } else {
    commit(types.SET_SELECT_ALL_STATE, false)
  }
}

export const setSelectAllState = ({ commit, dispatch, state }, data) => {
  commit(types.SET_SELECT_ALL_STATE, data)
}

export const selectAllBookings = ({ commit, dispatch, state }) => {
  if (state.selectedBookings.length === state.bookings.length) {
    commit(types.SET_SELECTED_BOOKINGS, [])
    commit(types.SET_SELECT_ALL_STATE, false)
  } else {
    let allBookingIds = state.bookings.map(inv => inv.id)
    commit(types.SET_SELECTED_BOOKINGS, allBookingIds)
    commit(types.SET_SELECT_ALL_STATE, true)
  }
}

export const resetSelectedBookings = ({ commit, dispatch, state }) => {
  commit(types.RESET_SELECTED_BOOKINGS)
}
export const setCustomer = ({ commit, dispatch, state }, data) => {
  commit(types.RESET_CUSTOMER)
  commit(types.SET_CUSTOMER, data)
}

export const resetCustomer = ({ commit, dispatch, state }) => {
  commit(types.RESET_CUSTOMER)
}

export const setTemplate = ({ commit, dispatch, state }, data) => {
  return new Promise((resolve, reject) => {
    commit(types.SET_TEMPLATE_ID, data)
    resolve({})
  })
}

export const selectCustomer = ({ commit, dispatch, state }, id) => {
  return new Promise((resolve, reject) => {
    window.axios.get(`/api/customers/${id}`).then((response) => {
      commit(types.RESET_SELECTED_CUSTOMER)
      commit(types.SELECT_CUSTOMER, response.data.customer)
      resolve(response)
    }).catch((err) => {
      reject(err)
    })
  })
}

export const resetSelectedCustomer = ({ commit, dispatch, state }, data) => {
  commit(types.RESET_SELECTED_CUSTOMER)
}

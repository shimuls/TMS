import mutations from './mutations'
import * as actions from './actions'
import * as getters from './getters'

const initialState = {
  bookings: [],
  bookingTemplateId: 1,
  selectedBookings: [],
  selectAllField: false,
  totalBookings: 0,
  selectedCustomer: null
}

export default {
  namespaced: true, 
  
  state: initialState,

  getters: getters,

  actions: actions,

  mutations: mutations
}

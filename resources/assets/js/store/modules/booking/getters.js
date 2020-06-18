export const bookings = (state) => state.bookings
export const selectAllField = (state) => state.selectAllField
export const getTemplateId = (state) => state.bookingTemplateId
export const selectedBookings = (state) => state.selectedBookings
export const totalBookings = (state) => state.totalBookings
export const selectedCustomer = (state) => state.selectedCustomer
export const getBooking = (state) => (id) => {
  let invId = parseInt(id)
  return state.bookings.find(booking => booking.id === invId)
}

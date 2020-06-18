<template>
  <div class="invoice-create-page main-content">
    <form v-if="!initLoading" action="" @submit.prevent="submitInvoiceData">
      <div class="page-header">
        <h3 v-if="$route.name === 'invoices.edit'" class="page-title">{{ $t('Schedule Booking') }}</h3>
        <h3 v-else class="page-title">{{ $t('Schedule Booking') }} </h3>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><router-link slot="item-title" to="/admin/dashboard">{{ $t('general.home') }}</router-link></li>
          <li class="breadcrumb-item"><router-link slot="item-title" to="/admin/bookings">{{ $tc('Bookings', 2) }}</router-link></li>
          <li v-if="$route.name === 'bookings.edit'" class="breadcrumb-item">{{ $t('Edit Bookings') }}</li>
          <li v-else class="breadcrumb-item">{{ $t('New Booking') }}</li>
        </ol>
        <div class="page-actions row">
          <a v-if="$route.name === 'bookings.edit'" :href="`/invoices/pdf/${newInvoice.unique_hash}`" target="_blank" class="mr-3 invoice-action-btn base-button btn btn-outline-primary default-size" outline color="theme">
            {{ $t('general.view_pdf') }}
          </a>
          <base-button
            :loading="isLoading"
            :disabled="isLoading"
            icon="save"
            color="theme"
            class="invoice-action-btn"
            type="submit">
            {{ $t('invoices.save_invoice') }}
          </base-button>
        </div>
      </div>
      <div class="row invoice-input-group">
        
        <div class="col invoice-input">
          <div class="row mb-3">
            <div class="col collapse-input">
              <label>{{ $tc('Booking',1) }} {{ $t('invoices.date') }}<span class="text-danger"> * </span></label>
              <base-date-picker
                v-model="newBooking.booking_date"
                :calendar-button="true"
                calendar-button-icon="calendar"
                @change="$v.newBooking.booking_date.$touch()"
              />
              
            </div>
            <div class="col collapse-input">
              <label>{{ $t('Schedule Date') }}<span class="text-danger"> * </span></label>
              <base-date-picker
                v-model="newBooking.due_date"
                
                :calendar-button="true"
                calendar-button-icon="calendar"
                @change="$v.newBooking.due_date.$touch()"
              />
              
            </div>
            <div class="col collapse-input">
              <label>{{ $t('Booking Number') }}<span class="text-danger"> * </span></label>
              <base-prefix-input
                v-model="invoiceNumAttribute"
                :invalid="$v.invoiceNumAttribute.$error"
                :prefix="invoicePrefix"
                icon="hashtag"
                @input="$v.invoiceNumAttribute.$touch()"
              />
              <span
                v-show="$v.invoiceNumAttribute.$error && !$v.invoiceNumAttribute.required"
                class="text-danger mt-1"
              >
                {{ $tc('validation.required') }}
              </span>
              <span
                v-show="!$v.invoiceNumAttribute.numeric" class="text-danger mt-1"
              >
                {{ $tc('validation.numbers_only') }}
              </span>
            </div>
          </div>
          <div class="row mt-4">
            
            <div class="col collapse-input">
              <label>{{ $t('Destination') }}</label>
              <base-input
                v-model="newInvoice.reference_number"
                :invalid="$v.newInvoice.reference_number.$error"
                icon="hashtag"
                @input="$v.newInvoice.reference_number.$touch()"
              />
              <div
                v-if="$v.newInvoice.reference_number.$error"
                class="text-danger"
              >
                {{ $tc('validation.ref_number_maxlength') }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <table class="item-table">
        <colgroup>
          <col style="width: 40%;">
          <col style="width: 10%;">
          <col style="width: 15%;">
          <col v-if="discountPerItem === 'YES'" style="width: 15%;">
          <col style="width: 15%;">
        </colgroup>
        <thead class="item-table-header">
          <tr>
            <th class="text-left">
              <span class="column-heading item-heading">
                {{ $tc('items.item',2) }}
              </span>
            </th>
            <th class="text-right">
              <span class="column-heading">
                {{ $t('invoices.item.quantity') }}
              </span>
            </th>
            <th class="text-left">
              <span class="column-heading">
                {{ $t('invoices.item.price') }}
              </span>
            </th>
            <th v-if="discountPerItem === 'YES'" class="text-right">
              <span class="column-heading">
                {{ $t('invoices.item.discount') }}
              </span>
            </th>
            <th class="text-right">
              <span class="column-heading amount-heading">
                {{ $t('invoices.item.amount') }}
              </span>
            </th>
          </tr>
        </thead>
        <draggable v-model="newBooking.items" class="item-body" tag="tbody" handle=".handle">
          <invoice-item
            v-for="(item, index) in newBooking.items"
            :key="item.id"
            :index="index"
            :item-data="item"
            :invoice-items="newBooking.items"
            :currency="currency"
            :tax-per-item="taxPerItem"
            :discount-per-item="discountPerItem"
            @remove="removeItem"
            @update="updateItem"
            @itemValidate="checkItemsData"
          />
        </draggable>
      </table>
      <div class="add-item-action" @click="addItem">
        <font-awesome-icon icon="shopping-basket" class="mr-2"/>
        {{ $t('invoices.add_item') }}
      </div>

      <div class="invoice-foot">
        <div>
          <label>{{ $t('invoices.notes') }}</label>
          <base-text-area
            v-model="newInvoice.notes"
            rows="3"
            cols="50"
            @input="$v.newInvoice.notes.$touch()"
          />
          <div v-if="$v.newInvoice.notes.$error">
            <span v-if="!$v.newInvoice.notes.maxLength" class="text-danger">{{ $t('validation.notes_maxlength') }}</span>
          </div>
          <label class="mt-3 mb-1 d-block">{{ $t('invoices.invoice_template') }} <span class="text-danger"> * </span></label>
          <base-button type="button" class="btn-template" icon="pencil-alt" right-icon @click="openTemplateModal" >
            <span class="mr-4"> {{ $t('invoices.template') }} {{ getTemplateId }} </span>
          </base-button>
        </div>

        <div class="invoice-total">
          <div class="section">
            <label class="invoice-label">{{ $t('invoices.sub_total') }}</label>
            <label class="invoice-amount">
              <div v-html="$utils.formatMoney(subtotal, currency)" />
            </label>
          </div>
          <div v-for="tax in allTaxes" :key="tax.tax_type_id" class="section">
            <label class="invoice-label">{{ tax.name }} - {{ tax.percent }}% </label>
            <label class="invoice-amount">
              <div v-html="$utils.formatMoney(tax.amount, currency)" />
            </label>
          </div>
          <div v-if="discountPerItem === 'NO' || discountPerItem === null" class="section mt-2">
            <label class="invoice-label">{{ $t('invoices.discount') }}</label>
            <div
              class="btn-group discount-drop-down"
              role="group"
            >
              <base-input
                v-model="discount"
                :invalid="$v.newBooking.discount_val.$error"
                input-class="item-discount"
                @input="$v.newBooking.discount_val.$touch()"
              />
              <v-dropdown :show-arrow="false">
                <button
                  slot="activator"
                  type="button"
                  class="btn item-dropdown dropdown-toggle"
                  data-toggle="dropdown"
                  aria-haspopup="true"
                  aria-expanded="false"
                >
                  {{ newInvoice.discount_type == 'fixed' ? currency.symbol : '%' }}
                </button>
                <v-dropdown-item>
                  <a class="dropdown-item" href="#" @click.prevent="selectFixed">
                    {{ $t('general.fixed') }}
                  </a>
                </v-dropdown-item>
                <v-dropdown-item>
                  <a class="dropdown-item" href="#" @click.prevent="selectPercentage">
                    {{ $t('general.percentage') }}
                  </a>
                </v-dropdown-item>
              </v-dropdown>
            </div>
          </div>

          <div v-if="taxPerItem === 'NO' || taxPerItem === null">
            <tax
              v-for="(tax, index) in newInvoice.taxes"
              :index="index"
              :total="subtotalWithDiscount"
              :key="tax.id"
              :tax="tax"
              :taxes="newInvoice.taxes"
              :currency="currency"
              :total-tax="totalSimpleTax"
              @remove="removeInvoiceTax"
              @update="updateTax"
            />
          </div>

          <base-popup v-if="taxPerItem === 'NO' || taxPerItem === null" ref="taxModal" class="tax-selector">
            <div slot="activator" class="float-right">
              + {{ $t('invoices.add_tax') }}
            </div>
            <tax-select-popup :taxes="newInvoice.taxes" @select="onSelectTax"/>
          </base-popup>

          <div class="section border-top mt-3">
            <label class="invoice-label">{{ $t('invoices.total') }} {{ $t('invoices.amount') }}:</label>
            <label class="invoice-amount total">
              <div v-html="$utils.formatMoney(total, currency)" />
            </label>
          </div>
        </div>
      </div>
    </form>
   <base-loader v-else /> 
  </div>
</template>

<script>
import draggable from 'vuedraggable'
import MultiSelect from 'vue-multiselect'
import InvoiceItem from './Item'
import BookingStub from '../../stub/booking'
import { mapActions, mapGetters } from 'vuex'
import moment from 'moment'
import { validationMixin } from 'vuelidate'
import Guid from 'guid'
import TaxStub from '../../stub/tax'
import Tax from './InvoiceTax'
const { required, between, maxLength, numeric } = require('vuelidate/lib/validators')

export default {
  components: {
    InvoiceItem,
    MultiSelect,
    Tax,
    draggable
  },
  mixins: [validationMixin],
  data () {
    return {
      newBooking: {
        booking_date: null,
        due_date: null,
        booking_number: null,
        user_id: null,
        
        sub_total: null,
        total: null,
        tax: null,
        notes: null,
        discount_type: 'fixed',
        discount_val: 0,
        discount: 0,
        reference_number: null,
        items: [{
          ...BookingStub,
          id: Guid.raw()
        
        }],
      },
      newInvoice: {
        //invoice_date: null,
        booking_date: null,
        due_date: null,
        booking_number: null,
        user_id: null,
        invoice_template_id: 1,
        sub_total: null,
        total: null,
        tax: null,
        notes: null,
        discount_type: 'fixed',
        discount_val: 0,
        discount: 0,
        reference_number: null,
        items: [{
          ...BookingStub,
          id: Guid.raw(),
          taxes: [{...TaxStub, id: Guid.raw()}]
        }],
        taxes: []
      },
      customers: [],
      itemList: [],
      invoiceTemplates: [],
      selectedCurrency: '',
      taxPerItem: null,
      discountPerItem: null,
      initLoading: false,
      isLoading: false,
      maxDiscount: 0,
      invoicePrefix: null,
      invoiceNumAttribute: null
    }
  },
  validations () {
    return {
      newInvoice: {
        invoice_date: {
          required
        },
        due_date: {
          required
        },
        discount_val: {
          between: between(0, this.subtotal)
        },
        notes: {
          maxLength: maxLength(255)
        },
        reference_number: {
          maxLength: maxLength(255)
        }
      },
      selectedCustomer: {
        required
      },
      invoiceNumAttribute: {
        required,
        numeric
      }
    }
  },
  computed: {
    ...mapGetters('general', [
      'itemDiscount'
    ]),
    ...mapGetters('currency', [
      'defaultCurrency'
    ]),
    ...mapGetters('invoice', [
      'getTemplateId',
      'selectedCustomer'
    ]),
    ...mapGetters('booking', [ 
      'bookings',     
      'selectedCustomer'
    ]),
    currency () {
      return this.selectedCurrency
    },
    subtotalWithDiscount () {
      return this.subtotal - this.newBooking.discount_val
    },
    total () {
      return this.subtotalWithDiscount + this.totalTax
    },
    subtotal () {
      return this.newInvoice.items.reduce(function (a, b) {
        return a + b['total']
      }, 0)
    },
    discount: {
      get: function () {
        return this.newBooking.discount
      },
      set: function (newValue) {
        if (this.newBooking.discount_type === 'percentage') {
          this.newBooking.discount_val = (this.subtotal * newValue) / 100
        } else {
          this.newBooking.discount_val = newValue * 100
        }

        this.newBooking.discount = newValue
      }
    },
    totalSimpleTax () {
      return window._.sumBy(this.newInvoice.taxes, function (tax) {
        if (!tax.compound_tax) {
          return tax.amount
        }

        return 0
      })
    },

    totalCompoundTax () {
      return window._.sumBy(this.newInvoice.taxes, function (tax) {
        if (tax.compound_tax) {
          return tax.amount
        }

        return 0
      })
    },
    totalTax () {
      if (this.taxPerItem === 'NO' || this.taxPerItem === null) {
        return this.totalSimpleTax + this.totalCompoundTax
      }

      return window._.sumBy(this.newInvoice.items, function (tax) {
        return tax.tax
      })
    },
    allTaxes () {
      let taxes = []

      this.newInvoice.items.forEach((item) => {
        item.taxes.forEach((tax) => {
          let found = taxes.find((_tax) => {
            return _tax.tax_type_id === tax.tax_type_id
          })

          if (found) {
            found.amount += tax.amount
          } else if (tax.tax_type_id) {
            taxes.push({
              tax_type_id: tax.tax_type_id,
              amount: tax.amount,
              percent: tax.percent,
              name: tax.name
            })
          }
        })
      })

      return taxes
    }
  },
  watch: {
    selectedCustomer (newVal) {
      if (newVal && newVal.currency) {
        this.selectedCurrency = newVal.currency
      } else {
        this.selectedCurrency = this.defaultCurrency
      }
    },
    subtotal (newValue) {
      if (this.newBooking.discount_type === 'percentage') {
        this.newBooking.discount_val = (this.newBooking.discount * newValue) / 100
      }
    }
  },
  created () {
    this.loadData()
    this.fetchInitialItems()
    this.resetSelectedCustomer()
    window.hub.$on('newTax', this.onSelectTax)
  },
  methods: {
    ...mapActions('modal', [
      'openModal'
    ]),
    ...mapActions('invoice', [
      'addInvoice',
      'fetchCreateInvoice',
      'fetchInvoice',
      'resetSelectedCustomer',
      'selectCustomer',
      'updateInvoice'
    ]),
    ...mapActions('booking', [
      'fetchBooking',
      'fetchCreateBooking',
      'addBooking',
      'resetSelectedCustomer',
      'selectCustomer',
      'updateBooking'
    ]),
    ...mapActions('item', [
      'fetchItems'
    ]),
    selectFixed () {
      if (this.newInvoice.discount_type === 'fixed') {
        return
      }

      this.newBooking.discount_val = this.newBooking.discount * 100
      this.newBooking.discount_type = 'fixed'
    },
    selectPercentage () {
      if (this.newInvoice.discount_type === 'percentage') {
        return
      }

      this.newBooking.discount_val = (this.subtotal * this.newBooking.discount) / 100

      this.newBooking.discount_type = 'percentage'
    },
    updateTax (data) {
      Object.assign(this.newBooking.taxes[data.index], {...data.item})
    },
    async fetchInitialItems () {
      await this.fetchItems({
        filter: {},
        orderByField: '',
        orderBy: ''
      })
    },
    async loadData () {
      if (this.$route.name === 'bookings.edit') {
        this.initLoading = true
        
        

        let response = await this.fetchBooking(this.$route.params.id)

        //let response = await this.fetchInvoice(this.$route.params.id)

        if (response.data) {
          this.selectCustomer(response.data.booking.user_id)
          this.newBooking = response.data.booking
          this.newBooking.booking_date = moment(response.data.booking.booking_date, 'YYYY-MM-DD').toString()
          this.newBooking.due_date = moment(response.data.booking.due_date, 'YYYY-MM-DD').toString() 
          this.discountPerItem = 0
          this.discount = 0
          this.newBooking.total = response.data.total
          this.newBooking.discount_val =0
          this.newBooking.discount =0

          // this.taxPerItem = response.data.tax_per_item
          // this.selectedCurrency = this.defaultCurrency
          //this.invoiceTemplates = response.data.invoiceTemplates
          this.invoicePrefix = response.data.booking_prefix
          this.invoiceNumAttribute = response.data.nextBookingNumber
        }
        this.initLoading = false

        

        return
      }

      this.initLoading = true
      let response = await this.fetchCreateBooking()
      if (response.data) {
        this.discountPerItem = response.data.discount_per_item
        this.taxPerItem = response.data.tax_per_item
        this.selectedCurrency = this.defaultCurrency
        //this.invoiceTemplates = 1// response.data.invoiceTemplates
        let today = new Date()
        this.newBooking.booking_date = moment(today).toString()
        this.newBooking.due_date = moment(today).add(7, 'days').toString()
        this.itemList = response.data.items
        this.invoicePrefix = 'BK' //response.data.invoice_prefix
        this.invoiceNumAttribute = response.data.nextBookingNumberAttribute
      }
      this.initLoading = false
    },
    removeCustomer () {
      this.resetSelectedCustomer()
    },
    editCustomer () {
      this.openModal({
        'title': this.$t('customers.edit_customer'),
        'componentName': 'CustomerModal',
        'id': this.selectedCustomer.id,
        'data': this.selectedCustomer
      })
    },
    openTemplateModal () {
      this.openModal({
        'title': this.$t('general.choose_template'),
        'componentName': 'InvoiceTemplate',
        'data': this.invoiceTemplates
      })
    },
    addItem () {
      this.newBooking.items.push({...BookingStub, id: Guid.raw(), taxes: [{...TaxStub, id: Guid.raw()}]})
    },
    removeItem (index) {
      this.newBooking.items.splice(index, 1)
    },
    updateItem (data) {
      Object.assign(this.newBooking.items[data.index], {...data.item})
    },
    submitInvoiceData () {
      //alert("Submit")
/*       if (!this.checkValid()) {
        return false
      } */

      this.isLoading = true
      this.newBooking.booking_number = this.invoicePrefix + '-' + this.invoiceNumAttribute

      let data = {
        ...this.newBooking,
        booking_date: moment(this.newBooking.booking_date).format('DD/MM/YYYY'),
        due_date: moment(this.newBooking.due_date).format('DD/MM/YYYY'),
        sub_total: this.subtotal,
        total: this.total,
        tax: this.totalTax,
        user_id: 1,
        invoice_template_id: 1 //this.getTemplateId
      }

/*       if (this.selectedCustomer != null) {
        data.user_id = this.selectedCustomer.id
      } */
      
      if (this.$route.name === 'bookings.edit') {
        this.submitUpdate(data)
        return
      }

      //this.submitSave(data)
    },
    submitSave (data) {
      this.addBooking(data).then((res) => {
        if (res.data) {
          window.toastr['success'](this.$t('invoices.created_message'))
          this.$router.push('/admin/bookings')
        }

        this.isLoading = false
      }).catch((err) => {
        this.isLoading = false
        if (err.response.data.errors.invoice_number) {
          window.toastr['error'](err.response.data.errors.invoice_number)
          return true
        }
        console.log(err)
      })
    },
    submitUpdate (data) {
      this.updateBooking(data).then((res) => {
        this.isLoading = false
        if (res.data.success) {
          window.toastr['success'](this.$t('invoices.updated_message'))
          this.$router.push('/admin/bookings')
        }

        if (res.data.error === 'invalid_due_amount') {
          window.toastr['error'](this.$t('invoices.invalid_due_amount_message'))
        }
      }).catch((err) => {
        this.isLoading = false
        if (err.response.data.errors.invoice_number) {
          window.toastr['error'](err.response.data.errors.invoice_number)
          return true
        }
        console.log(err)
      })
    },
    checkItemsData (index, isValid) {
      this.newInvoice.items[index].valid = isValid
    },
    onSelectTax (selectedTax) {
      let amount = 0

      if (selectedTax.compound_tax && this.subtotalWithDiscount) {
        amount = ((this.subtotalWithDiscount + this.totalSimpleTax) * selectedTax.percent) / 100
      } else if (this.subtotalWithDiscount && selectedTax.percent) {
        amount = (this.subtotalWithDiscount * selectedTax.percent) / 100
      }

      this.newInvoice.taxes.push({
        ...TaxStub,
        id: Guid.raw(),
        name: selectedTax.name,
        percent: selectedTax.percent,
        compound_tax: selectedTax.compound_tax,
        tax_type_id: selectedTax.id,
        amount
      })

      this.$refs.taxModal.close()
    },
    removeInvoiceTax (index) {
      this.newInvoice.taxes.splice(index, 1)
    },
    checkValid () {
      this.$v.newInvoice.$touch()
      this.$v.selectedCustomer.$touch()

      window.hub.$emit('checkItems')
      let isValid = true
      this.newInvoice.items.forEach((item) => {
        if (!item.valid) {
          isValid = false
        }
      })
      if (!this.$v.selectedCustomer.$invalid && this.$v.newInvoice.$invalid === false && isValid === true) {
        return true
      }
      return false
    }
   }
}
</script>

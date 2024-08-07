import LayoutObserver from './layout-observer'
import { mapStates } from './store/helper'
import { getCell, getColumnByCell, getRowIdentity } from './util'
import { getStyle, hasClass, removeClass, addClass } from 'element-ui/src/utils/dom'
import debounce from 'throttle-debounce/debounce'

export default {
  name: 'ElTableFooter',

  mixins: [LayoutObserver],

  render(h) {
    let sums = []
    if (this.summaryMethod) {
      sums = this.summaryMethod({ columns: this.columns, data: this.store.states.data })
    }
    if (sums === false) {
      sums = []
      this.columns.forEach((column, index) => {
        if (index === 0) {
          sums[index] = this.sumText
          return
        }
        const values = this.store.states.data.map(item => Number(item[column.property]))
        const precisions = []
        let notNumber = true
        values.forEach(value => {
          if (!isNaN(value)) {
            notNumber = false
            let decimal = ('' + value).split('.')[1]
            precisions.push(decimal ? decimal.length : 0)
          }
        })
        const precision = Math.max.apply(null, precisions)
        if (!notNumber) {
          sums[index] = values.reduce((prev, curr) => {
            const value = Number(curr)
            if (!isNaN(value)) {
              return parseFloat((prev + curr).toFixed(Math.min(precision, 20)))
            } else {
              return prev
            }
          }, 0)
        } else {
          sums[index] = ''
        }
      })
    }
    return (
      <table class='el-table__footer' cellspacing='0' cellpadding='0' border='0'>
        <colgroup>
          {this.columns.map(column => (
            <col name={column.id} key={column.id} />
          ))}
        </colgroup>
        <tbody>
          <tr>
            {this.columns.map((column, cellIndex) => (
              <td
                key={cellIndex}
                colspan={column.colSpan}
                rowspan={column.rowSpan}
                style={this.getFooterCellStyle(column)}
                class={[...this.getRowClasses(column, cellIndex), 'el-table__cell']}
                on-mouseenter={$event => this.handleCellMouseEnter($event, sums[cellIndex].label)}
                on-mouseleave={this.handleCellMouseLeave}
              >
                <div
                  style={{
                    'word-break': 'keep-all',
                    color: (sums[cellIndex] && sums[cellIndex].color) || '',
                  }}
                  class={['cell', column.labelClassName, 'el-tooltip']}
                >
                  {sums[cellIndex] && sums[cellIndex].label}
                </div>
              </td>
            ))}
          </tr>
          <el-tooltip
            effect={this.table.tooltipEffect}
            placement='top'
            ref='tooltip'
            content={this.tooltipContent}
          ></el-tooltip>
        </tbody>
      </table>
    )
  },

  props: {
    fixed: String,
    store: {
      required: true,
    },
    summaryMethod: Function,
    sumText: String,
    border: Boolean,
    defaultSort: {
      type: Object,
      default() {
        return {
          prop: '',
          order: '',
        }
      },
    },
  },

  computed: {
    table() {
      return this.$parent
    },

    ...mapStates({
      columns: 'columns',
      isAllSelected: 'isAllSelected',
      leftFixedLeafCount: 'fixedLeafColumnsLength',
      rightFixedLeafCount: 'rightFixedLeafColumnsLength',
      columnsCount: states => states.columns.length,
      leftFixedCount: states => states.fixedColumns.length,
      rightFixedCount: states => states.rightFixedColumns.length,
    }),
  },

  data() {
    return { activateTooltip: '', tooltipContent: '' }
  },

  created() {
    this.activateTooltip = debounce(50, tooltip => tooltip.handleShowPopper())
  },

  methods: {
    isCellHidden(index, columns, column) {
      if (this.fixed === true || this.fixed === 'left') {
        return index >= this.leftFixedLeafCount
      } else if (this.fixed === 'right') {
        let before = 0
        for (let i = 0; i < index; i++) {
          before += columns[i].colSpan
        }
        return before < this.columnsCount - this.rightFixedLeafCount
      } else if (!this.fixed && column.fixed) {
        // hide cell when footer instance is not fixed and column is fixed
        return true
      } else {
        return index < this.leftFixedCount || index >= this.columnsCount - this.rightFixedCount
      }
    },

    getRowClasses(column, cellIndex) {
      const classes = [column.id, column.align, column.labelClassName]
      if (column.className) {
        classes.push(column.className)
      }
      // if (this.isCellHidden(cellIndex, this.columns, column)) {
      //   classes.push('is-hidden');
      // }

      // 添加边缘固定列样式
      switch (column.isEdge) {
        case 'left':
          classes.push('is-edge-left-column')
          break
        case 'right':
          classes.push('is-edge-right-column')
          break
      }

      if (!column.children) {
        classes.push('is-leaf')
      }
      return classes.filter(i => i)
    },

    getFooterCellStyle(column) {
      const footerCellStyle = {}

      if (this.tableLayout.scrollX) {
        // 固定列添加粘性定位样式
        if (column.fixed) {
          footerCellStyle.position = 'sticky'
          footerCellStyle.zIndex = '1'
        }
        // 补足滚动条宽度
        column.left !== undefined && (footerCellStyle.left = `${column.left}px`)
        column.right !== undefined && (footerCellStyle.right = `${column.right}px`)
      }

      return footerCellStyle
    },

    handleCellMouseEnter(event, row) {
      const cell = getCell(event)

      // // 判断是否text-overflow, 如果是就显示tooltip
      const cellChild = event.target.querySelector('.cell')
      if (!(hasClass(cellChild, 'el-tooltip') && cellChild.childNodes.length)) {
        return
      }
      // // use range width instead of scrollWidth to determine whether the text is overflowing
      // // to address a potential FireFox bug: https://bugzilla.mozilla.org/show_bug.cgi?id=1074543#c3
      const range = document.createRange()
      range.setStart(cellChild, 0)
      range.setEnd(cellChild, cellChild.childNodes.length)
      const rangeWidth = range.getBoundingClientRect().width
      const padding =
        (parseInt(getStyle(cellChild, 'paddingLeft'), 10) || 0) +
        (parseInt(getStyle(cellChild, 'paddingRight'), 10) || 0)
      if (
        (rangeWidth + padding > cellChild.offsetWidth ||
          cellChild.scrollWidth > cellChild.offsetWidth) &&
        this.$refs.tooltip
      ) {
        const tooltip = this.$refs.tooltip
        // // TODO 会引起整个 Table 的重新渲染，需要优化
        this.tooltipContent = cell.innerText || cell.textContent
        tooltip.referenceElm = cell
        tooltip.$refs.popper && (tooltip.$refs.popper.style.display = 'none')
        tooltip.doDestroy()
        tooltip.setExpectedState(true)
        this.activateTooltip(tooltip)
      }
    },

    handleCellMouseLeave(event) {
      const tooltip = this.$refs.tooltip
      if (tooltip) {
        tooltip.setExpectedState(false)
        tooltip.handleClosePopper()
      }
      const cell = getCell(event)
      if (!cell) return
      const oldHoverState = this.table.hoverState || {}
      this.table.$emit(
        'cell-mouse-leave',
        oldHoverState.row,
        oldHoverState.column,
        oldHoverState.cell,
        event
      )
    },
  },
}

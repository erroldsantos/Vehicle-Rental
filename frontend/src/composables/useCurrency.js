/**
 * Currency Utility for Philippine Peso (PHP)
 */

export function useCurrency() {
  /**
   * Format amount as Philippine Peso
   * @param {number|string} amount - The amount to format
   * @returns {string} Formatted currency string
   */
  const formatPeso = (amount) => {
    const num = parseFloat(amount) || 0
    return num.toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    })
  }

  /**
   * Get currency symbol
   * @returns {string} Currency symbol
   */
  const currencySymbol = () => '₱'

  /**
   * Format with symbol
   * @param {number|string} amount - The amount to format
   * @returns {string} Formatted currency with symbol
   */
  const formatWithSymbol = (amount) => {
    return `₱${formatPeso(amount)}`
  }

  return {
    formatPeso,
    currencySymbol,
    formatWithSymbol
  }
}

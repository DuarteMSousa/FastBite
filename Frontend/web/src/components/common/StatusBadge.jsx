import { statusLabelForKind, statusTone } from '../../utils/statusLabels'

export function StatusBadge({ kind = 'order', status }) {
  return (
    <span className={`rb-chip ${statusTone(status)}`}>{statusLabelForKind(kind, status)}</span>
  )
}

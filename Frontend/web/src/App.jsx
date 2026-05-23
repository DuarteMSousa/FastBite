import { ErrorBoundary } from './components/common/ErrorBoundary'
import { RestaurantWebShell } from './screens/restaurant/RestaurantWebShell'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'

function App() {
  return (
    <ErrorBoundary>
      <BrowserRouter>
        <Routes>
          <Route path="/" element={<Navigate to="/restaurant/login" replace />} />
          <Route path="/restaurant/*" element={<RestaurantWebShell />} />
          <Route path="*" element={<Navigate to="/restaurant/login" replace />} />
        </Routes>
      </BrowserRouter>
    </ErrorBoundary>
  )
}

export default App

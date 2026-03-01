import api from './api.js'

export function login(email, password) {
  return api.post('/login', { email, password })
}

export function logout() {
  return api.post('/logout')
}

export function getToken() {
  return localStorage.getItem('api_token')
}

export function setToken(token) {
  localStorage.setItem('api_token', token)
}

export function removeToken() {
  localStorage.removeItem('api_token')
}

export function isAuthenticated() {
  return !!getToken()
}

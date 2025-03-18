/**
 * assets/ts/types/api.ts
 * Types partagés pour les réponses API
 */

// Interface de base pour les réponses API
export interface ApiResponse {
  success: boolean;
  message: string;
}

// Extensions spécifiques
export interface CustomerResponse extends ApiResponse {
  customer?: Customer;
}

export interface UserResponse extends ApiResponse {
  user?: User;
}

export interface ProfileResponse extends ApiResponse {
  newImagePath?: string;
}

// Types d'entités
export interface Customer {
  id: string;
  name: string;
  reference: string;
  address: string;
  zipcode: string;
  city: string;
  country: string;
  vat?: string;
  siren?: string;
}

export interface User {
  id: string;
  firstName: string;
  lastName: string;
  email: string;
  role: string;
} 
/**
 * assets/ts/types/project.ts
 * Types pour les fonctionnalités liées aux projets
 */

// Pour que les déclarations globales fonctionnent
export {};

/**
 * Interface pour le module de projet
 */
export interface ProjectModuleInterface {
  formatDate: (dateString: string) => string;
  announceForScreenReader: (message: string) => void;
  initProjectPage: () => void;
  setupProjectFilters: () => void;
  updateTaskStatus: (taskId: string, newStatus: string, onSuccess?: (data: any) => void) => void;
  updateTaskPosition: (taskId: string, newColumn: string, taskOrder: Array<{id: string, rank: number}>, onSuccess?: (data: any) => void) => void;
  setupTaskManagement: () => void;
  init: () => void;
}

/**
 * Structure d'un projet
 */
export interface Project {
  id: string;
  name: string;
  description: string;
  status: string;
  startDate: string;
  endDate: string;
  clientId: string;
  clientName: string;
}

/**
 * Structure d'une tâche
 */
export interface Task {
  id: string;
  name: string;
  description: string;
  status: string;
  type: string;
  complexity: string;
  dateFrom: string;
  dateTo: string;
  projectId: string;
  rank: number;
}

/**
 * Déclaration globale pour accéder au ProjectModule depuis n'importe où
 */
declare global {
  interface Window {
    ProjectModule: ProjectModuleInterface;
  }
} 
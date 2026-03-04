// useCompany composable - to be implemented
import { ref } from 'vue';

export function useCompany() {
  const company = ref(null);
  
  return {
    company
  };
}

import 'package:flutter/material.dart';
import '../services/api_service.dart';

class QuizScreen extends StatefulWidget {
  final int? temaId;      // Ahora es opcional
  final int? subtemaId;   // Nuevo campo opcional
  final String titulo;    // Título genérico para mostrar arriba

  const QuizScreen({
    super.key, 
    this.temaId, 
    this.subtemaId,
    required this.titulo
  });

  @override
  State<QuizScreen> createState() => _QuizScreenState();
}

class _QuizScreenState extends State<QuizScreen> {
  final ApiService apiService = ApiService();
  
  // Estado del Quiz
  bool _cargando = true;
  bool _enviando = false;
  List<dynamic> _preguntas = [];
  int _quizId = 0;
  
  // Respuestas del usuario
  // Mapa para guardar: { indice_pregunta: "opcion_seleccionada" }
  final Map<int, String> _respuestasUsuario = {};
  
  // Control de navegación
  final PageController _pageController = PageController();
  int _preguntaActual = 0;

  @override
  void initState() {
    super.initState();
    _iniciarQuiz();
  }

  Future<void> _iniciarQuiz() async {
    try {
      // 1. Pedimos al backend que genere las preguntas
      final datos = await apiService.generarCuestionario(
        temaId: widget.temaId,
        subtemaId: widget.subtemaId, 
        cantidad: 5
      );
      
      setState(() {
        _quizId = datos['quiz_id'];
        _preguntas = datos['preguntas'];
        _cargando = false;
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error al generar quiz: $e')),
      );
      Navigator.pop(context); // Salir si falla
    }
  }

  Future<void> _finalizarQuiz() async {
    setState(() => _enviando = true);

    try {
      // 1. Convertimos las respuestas al formato que pide el backend
      // Backend espera: [{'id': 1, 'seleccion': 'a'}, ...]
      List<Map<String, dynamic>> listaParaEnviar = [];
      
      _respuestasUsuario.forEach((index, respuesta) {
        // Usamos el ID real de la pregunta si viene del backend, o el índice + 1
        int preguntaId = _preguntas[index]['id'] ?? (index + 1);
        
        listaParaEnviar.add({
          'id': preguntaId,
          'seleccion': respuesta // ej: "a"
        });
      });

      // 2. Enviamos a calificar
      final resultado = await apiService.responderCuestionario(_quizId, listaParaEnviar);

      if (!mounted) return;

      // 3. Mostrar resultados (Navegar a pantalla de resultados o mostrar alerta)
      _mostrarDialogoResultado(resultado);

    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error al calificar: $e')),
      );
      setState(() => _enviando = false);
    }
  }

  void _mostrarDialogoResultado(Map<String, dynamic> resultado) {
    int nota = resultado['calificacion'] ?? 0;
    String feedback = resultado['retroalimentacion'] ?? 'Sin comentarios';
    bool aprobado = nota >= 60;

    showModalBottomSheet(
      context: context,
      isDismissible: false,
      enableDrag: false,
      isScrollControlled: true, // <--- 1. Permite que el modal sea más alto
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(25.0)),
      ),
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.6, // Ocupa el 60% de la pantalla al abrir
        minChildSize: 0.4,
        maxChildSize: 0.9, // Puede estirarse hasta el 90%
        expand: false,
        builder: (_, controller) => Container(
          padding: const EdgeInsets.all(20),
          child: Column(
            children: [
              // Barra gris pequeña para indicar que se puede deslizar (estética)
              Container(
                width: 40,
                height: 5,
                margin: const EdgeInsets.only(bottom: 20),
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
              
              // Contenido con Scroll
              Expanded(
                child: ListView( // <--- 2. Usamos ListView para que el texto tenga scroll
                  controller: controller,
                  children: [
                    Icon(
                      aprobado ? Icons.emoji_events : Icons.sentiment_dissatisfied,
                      size: 80,
                      color: aprobado ? Colors.amber : Colors.redAccent,
                    ),
                    const SizedBox(height: 10),
                    Text(
                      aprobado ? '¡Felicidades!' : 'Sigue intentando',
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 5),
                    Text(
                      'Calificación: $nota/100',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20, 
                        color: aprobado ? Colors.green : Colors.red,
                        fontWeight: FontWeight.bold
                      ),
                    ),
                    const Divider(height: 30, thickness: 1),
                    const Text(
                      "Retroalimentación de la IA:",
                      style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      feedback, 
                      style: const TextStyle(fontSize: 16, height: 1.5),
                      textAlign: TextAlign.justify, // Texto justificado se ve mejor
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),

              // Botón Fijo al final
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    backgroundColor: Colors.blueAccent,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: () {
                    Navigator.pop(context); // Cierra modal
                    Navigator.pop(context); // Sale del Quiz
                  },
                  child: const Text('Terminar Revisión', style: TextStyle(fontSize: 18)),
                ),
              )
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_cargando) {
      return const Scaffold(
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(),
              SizedBox(height: 20),
              Text('La IA está preparando tu examen...'),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('Quiz: ${widget.titulo}'),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(6.0),
          child: LinearProgressIndicator(
            value: (_preguntaActual + 1) / _preguntas.length,
            backgroundColor: Colors.grey[300],
            valueColor: const AlwaysStoppedAnimation<Color>(Colors.blue),
          ),
        ),
      ),
      body: PageView.builder(
        controller: _pageController,
        physics: const NeverScrollableScrollPhysics(), // Evita deslizar con el dedo, obliga a usar botones
        itemCount: _preguntas.length,
        itemBuilder: (context, index) {
          return _buildPreguntaCard(index);
        },
      ),
    );
  }

  Widget _buildPreguntaCard(int index) {
    final pregunta = _preguntas[index];
    final List<dynamic> opciones = pregunta['opciones']; // ["a) ...", "b) ..."]

    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Pregunta ${index + 1} de ${_preguntas.length}',
            style: const TextStyle(color: Colors.grey, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 10),
          Text(
            pregunta['pregunta'],
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 20),
          
          // Lista de Opciones
          Expanded(
            child: ListView.builder(
              itemCount: opciones.length,
              itemBuilder: (ctx, i) {
                String textoOpcion = opciones[i];
                // Extraemos la letra "a", "b" para guardar solo eso
                // Asumimos formato "a) Texto"
                String letra = textoOpcion.split(')')[0].trim(); 
                bool seleccionado = _respuestasUsuario[index] == letra;

                return Card(
                  color: seleccionado ? Colors.blue.shade50 : Colors.white,
                  elevation: seleccionado ? 4 : 1,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                    side: BorderSide(
                      color: seleccionado ? Colors.blue : Colors.transparent,
                      width: 2
                    )
                  ),
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    title: Text(textoOpcion),
                    leading: CircleAvatar(
                      backgroundColor: seleccionado ? Colors.blue : Colors.grey.shade200,
                      child: Text(
                        letra.toUpperCase(),
                        style: TextStyle(
                          color: seleccionado ? Colors.white : Colors.black87
                        ),
                      ),
                    ),
                    onTap: () {
                      setState(() {
                        _respuestasUsuario[index] = letra;
                      });
                    },
                  ),
                );
              },
            ),
          ),

          // Botones de Navegación
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              // Botón Anterior
              if (index > 0)
                TextButton(
                  onPressed: () {
                    _pageController.previousPage(
                      duration: const Duration(milliseconds: 300), 
                      curve: Curves.ease
                    );
                    setState(() => _preguntaActual--);
                  },
                  child: const Text('Anterior'),
                )
              else
                const SizedBox(), // Espacio vacío para alinear

              // Botón Siguiente / Finalizar
              ElevatedButton(
                onPressed: _respuestasUsuario[index] == null 
                  ? null // Deshabilita si no respondió
                  : () {
                      if (index < _preguntas.length - 1) {
                        _pageController.nextPage(
                          duration: const Duration(milliseconds: 300), 
                          curve: Curves.ease
                        );
                        setState(() => _preguntaActual++);
                      } else {
                        // Última pregunta
                        if (!_enviando) _finalizarQuiz();
                      }
                    },
                child: _enviando 
                  ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                  : Text(index == _preguntas.length - 1 ? 'FINALIZAR' : 'Siguiente'),
              ),
            ],
          )
        ],
      ),
    );
  }
}